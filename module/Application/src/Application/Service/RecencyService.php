<?php

namespace Application\Service;

use Exception;
use GuzzleHttp;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use Application\Model\RecencyTable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RecencyService
{

    public $sm = null;

    public function __construct($sm)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function getRecencyDetails($params)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        $acl = $this->sm->get('AppAcl');
        return $recencyDb->fetchRecencyDetails($params, $acl);
    }

    public function getReqVlTestOnVlsmDetails($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchReqVlTestOnVlsmDetails($params);
    }

    public function getSampleId()
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchSampleId();
    }

    public function addRecencyDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->addRecencyDetails($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Recency details added successfully';
                // Add event log
                $subject                = $result;
                $eventType              = 'Recency details-add';
                $action                 = 'Added a new Recency details for patient id' . ucwords($params['patientId']);
                $resourceName           = 'Recency Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getRecencyDetailsById($recencyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetailsById($recencyId);
    }

    public function getSamplesWithoutManifestCode($testingSiteId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->getSamplesWithoutManifestCode($testingSiteId);
    }
    public function fetchSamplesByManifestId($manifestId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchSamplesByManifestId($manifestId);
    }

    public function updateRecencyDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->updateRecencyDetails($params);
            if ($result > 0) {
                $adapter->commit();
                // Add Event log
                $subject                = $result;
                $eventType              = 'Recency details-edit';
                $action                 = 'Edited  Recency details for patient id ' . ucwords($params['patientId']);
                $resourceName           = 'Recency Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Recency details updated successfully';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getAllRecencyListApi($params)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyListApi($params);
    }

    public function getAllRecencyResultWithVlListApi($params)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyResultWithVlListApi($params);
    }

    public function getAllPendingVlResultListApi($params)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllPendingVlResultListApi($params);
    }

    public function addRecencyDataApi($params)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->addRecencyDetailsApi($params);
    }

    public function getRecencyOrderDetails($id)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyOrderDetails($id);
    }

    public function getTesterData($val)
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->getActiveTester($val);
    }

    public function getSampleData($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchSampleData($params);
    }
    public function updateVlSampleResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->updateVlSampleResult($params);
    }

    public function exportRecencyData()
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 30000);
        try {
            $common = new CommonService();
            $queryContainer = new Container('query');


            $output = array();

            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportRecencyDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportRecencyDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (!empty($sResult)) {

                $excel = new Spreadsheet();
                $sheet = $excel->getActiveSheet();
                $styleArray = array(
                    'font' => array(
                        'bold' => true,
                        'size' => 12,
                    ),
                    'alignment' => array(
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ),
                    'borders' => array(
                        'outline' => array(
                            'style' => Border::BORDER_THIN,
                        ),
                    ),
                );

                $borderStyle = array(
                    'borders' => array(
                        'outline' => array(
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => array('argb' => '000000'),
                        ),
                    ),
                );

                foreach ($sResult as $aRow) {
                    $row = array();
                    $formInitiationDate = '';
                    if ($aRow['form_initiation_datetime'] != '' && $aRow['form_initiation_datetime'] != '0000-00-00 00:00:00' && $aRow['form_initiation_datetime'] != null) {
                        $formInitiationAry = explode(" ", $aRow['form_initiation_datetime']);
                        $formInitiationDate = $common->humanDateFormat($formInitiationAry[0]) . " " . $formInitiationAry[1];
                    }
                    $formTransferDate = '';
                    if ($aRow['form_transfer_datetime'] != '' && $aRow['form_transfer_datetime'] != '0000-00-00 00:00:00' && $aRow['form_transfer_datetime'] != null) {
                        $formTransferAry = explode(" ", $aRow['form_transfer_datetime']);
                        $formTransferDate = $common->humanDateFormat($formTransferAry[0]) . " " . $formTransferAry[1];
                    }

                    $savedDateTime = '';
                    if ($aRow['form_saved_datetime'] != '' && $aRow['form_saved_datetime'] != '0000-00-00 00:00:00' && $aRow['form_saved_datetime'] != null) {
                        $savedDateTimeArray = explode(" ", $aRow['form_saved_datetime']);
                        $savedDateTime = $common->humanDateFormat($savedDateTimeArray[0]) . " " . $savedDateTimeArray[1];
                    }
                    $addedOn = '';
                    if ($aRow['added_on'] != '' && $aRow['added_on'] != '0000-00-00 00:00:00' && $aRow['added_on'] != null) {
                        $addedOnArray = explode(" ", $aRow['added_on']);
                        $addedOn = $common->humanDateFormat($addedOnArray[0]) . " " . $addedOnArray[1];
                    }
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['patient_id'];
                    $row[] = ucwords($aRow['province_name']);
                    $row[] = $aRow['district_name'];
                    $row[] = ucwords($aRow['facility_name']);
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $aRow['testing_facility_type_name'];
                    $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
                    $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
                    $row[] = $aRow['sample_name'];
                    $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
                    $row[] = (isset($aRow['recency_test_performed']) && !empty($aRow['recency_test_performed']) && ($aRow['recency_test_performed'] == 1)) ? 'Not Performed' : '';
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = ucwords($aRow['control_line']);
                    $row[] = ucwords($aRow['positive_verification_line']);
                    $row[] = ucwords($aRow['long_term_verification_line']);
                    $row[] = $aRow['kit_lot_no'];
                    $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                    $row[] = $aRow['vl_result'];
                    $row[] = $aRow['vl_sample_status'];
                    $row[] = ucwords($aRow['tester_name']);
                    $row[] = $common->humanDateFormat($aRow['dob']);
                    $row[] = $aRow['age'];
                    $row[] = ucwords($aRow['gender']);
                    $row[] = str_replace("_", " ", ucwords($aRow['marital_status']));
                    $row[] = ucwords($aRow['residence']);
                    $row[] = str_replace("_", " ", ucwords($aRow['education_level']));
                    $row[] = ucwords($aRow['name']);
                    $row[] = str_replace("_", " ", ucwords($aRow['pregnancy_status']));
                    $row[] = str_replace("_", "-", $aRow['current_sexual_partner']);
                    $row[] = ucwords($aRow['past_hiv_testing']);
                    $row[] = ucwords($aRow['last_hiv_status']);
                    $row[] = ucwords($aRow['patient_on_art']);
                    $row[] = str_replace("_", " ", ucwords($aRow['test_last_12_month']));
                    $row[] = str_replace("_", " ", ucwords($aRow['exp_violence_last_12_month']));
                    $row[] = $formInitiationDate;
                    $row[] = $formTransferDate;
                    $row[] = $savedDateTime;
                    $row[] = $aRow['mac_no'];
                    $row[] = $aRow['cell_phone_number'];
                    $row[] = $addedOn;
                    $row[] = $aRow['latitude'];
                    $row[] = $aRow['longitude'];
                    $output[] = $row;
                }
            }

            $sheet->setCellValue('A1', html_entity_decode('Sample ID', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Patient ID', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('Province', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('District', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('Testing Modality', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H1', html_entity_decode('Sample Collection Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('Sample Receipt Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J1', html_entity_decode('Received Specimen Type', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K1', html_entity_decode('HIV Diagnosis Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L1', html_entity_decode('Recent Test not performed', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('M1', html_entity_decode('HIV Recency Test Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('N1', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('O1', html_entity_decode('Verification Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('P1', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('Q1', html_entity_decode('Kit Lot Number', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('R1', html_entity_decode('Kit Expiry Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('S1', html_entity_decode('Assay Outcome', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('T1', html_entity_decode('Final Outcome', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('U1', html_entity_decode('VL Test Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('V1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('W1', html_entity_decode('VL Sample Status', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('X1', html_entity_decode('Tester Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('Y1', html_entity_decode('DOB', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('Z1', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AA1', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AB1', html_entity_decode('Martial Status', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AC1', html_entity_decode('Residence', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AD1', html_entity_decode('Education Level', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AE1', html_entity_decode('Risk Population', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AF1', html_entity_decode('Pregnancy Status', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AG1', html_entity_decode('Current Sexual Partner', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AH1', html_entity_decode('Past HIV Testing', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AI1', html_entity_decode('Last HIV Status', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AJ1', html_entity_decode('Patient On ART', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AK1', html_entity_decode('Last 12 Month', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AL1', html_entity_decode('Experienced Violence Last 12 Month', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AM1', html_entity_decode('Form Initiation Datetime', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AN1', html_entity_decode('Form Transfer Datetime', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AO1', html_entity_decode('Form Saved Datetime', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AP1', html_entity_decode('Device ID', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AQ1', html_entity_decode('Device Phone Number', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AR1', html_entity_decode('Data Added On', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AS1', html_entity_decode('Latitude', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('AT1', html_entity_decode('Longitude', ENT_QUOTES, 'UTF-8'));

            $sheet->getStyle('A1:AT1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Recency-Data-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);

            // Add event log
            $subject                = $filename;
            $eventType              = 'Recency data-export';
            $action                 = 'Exported Recency data ';
            $resourceName           = 'Recency data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            error_log("RECENCY-DATA-EXPORT : " . $exc->getMessage());
            error_log($exc->getTraceAsString());
            return "";
        }
    }

    public function getAllRecencyResultWithVlList($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        $acl = $this->sm->get('AppAcl');
        return $recencyDb->fetchAllRecencyResultWithVlList($params, $acl);
    }

    public function getAllLtResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        $acl = $this->sm->get('AppAcl');
        return $recencyDb->fetchAllLtResult($params, $acl);
    }

    // Export Result for Recent Infected :

    public function exportRInfectedData($params)
    {

        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportRecentResultDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportRecentResultDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['facility_name'];
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = ucwords($aRow['control_line']);
                    $row[] = ucwords($aRow['positive_verification_line']);
                    $row[] = ucwords($aRow['long_term_verification_line']);
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['vl_result'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = ucwords($aRow['gender']);
                    $row[] = $aRow['age'];
                    $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
                    $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
                    $row[] = $aRow['sample_name'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('Date Of Testing', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('Verification Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('Assasy Test Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J1', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K1', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L1', html_entity_decode('Sample Collection Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('M1', html_entity_decode('Sample Receipt Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('N1', html_entity_decode('Received Specimen Type', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('O1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('P1', html_entity_decode('VL Test Date', ENT_QUOTES, 'UTF-8'));


            $sheet->getStyle('A1:P1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Recent-Infections-Data-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'Recency Infections data-export';
            $action                 = 'Exported Recency Infections data ';
            $resourceName           = 'Recency  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("Generate-R-Infected--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    // All Long Term Infected Data:

    public function exportLongTermInfected($params)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 30000);
        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportLongtermDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportLongtermDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['facility_name'];
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = ucwords($aRow['control_line']);
                    $row[] = ucwords($aRow['positive_verification_line']);
                    $row[] = ucwords($aRow['long_term_verification_line']);
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['vl_result'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = ucwords($aRow['gender']);
                    $row[] = $aRow['age'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );
            $sheet->setCellValue('A1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('Date Of Testing', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('Verification Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('Assasy Test Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J1', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K1', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('M1', html_entity_decode('VL Test Date', ENT_QUOTES, 'UTF-8'));

            $sheet->getStyle('A1:M1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Long-term-Infection-Data-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'Long term Infections data-export';
            $action                 = 'Exported Long term Infections data ';
            $resourceName           = 'Long term  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getTatReportAPI($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchTatReportAPI($params);
    }

    public function getTatReport($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchTatReport($params);
    }

    //tat report
    public function exportTatReport($params)
    {

        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportTatQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportTatQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $aRow['sample_id'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $aRow['final_outcome'];
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                    $row[] = date('d-M-Y', strtotime($aRow['vl_result_entry_date']));
                    $row[] = $aRow['diffInDays'];
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );


            $sheet->setCellValue('A1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('Recency Testing Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('VL Tested Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('VL Entered Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('Difference(TAT)', ENT_QUOTES, 'UTF-8'));


            $sheet->getStyle('A1:G1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'TAT-Report-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'TAT report data-export';
            $action                 = 'Exported TAT report data ';
            $resourceName           = 'TAT report  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getSampleResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchSampleResult($params);
    }

    public function getEmailSendResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchEmailSendResult($params);
    }

    public function updateEmailSendResult($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $configResult = $this->sm->get('Config');
            $result = $recencyDb->updateEmailSendResult($params, $configResult);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Mail sent successfully';
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function updateOutcome()
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->updateOutcome();
    }

    public function uploadResult()
    {
        $recencyDb = $this->sm->get('RecencyTable');
        $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $allowedExtensions = array('xls', 'xlsx', 'csv');
        $fileName = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['fileName']['name']);
        $fileName = str_replace(" ", "-", $fileName);
        $ranNumber = str_pad(rand(0, pow(10, 6) - 1), 6, '0', STR_PAD_LEFT);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileName = $ranNumber . "." . $extension;
        if (in_array($extension, $allowedExtensions)) {
            if (!file_exists(UPLOAD_PATH) && !is_dir(UPLOAD_PATH)) {
                mkdir(APPLICATION_PATH . DIRECTORY_SEPARATOR . "uploads");
            }

            if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName) && move_uploaded_file($_FILES['fileName']['tmp_name'], UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName)) {
                $objPHPExcel = IOFactory::load(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName);
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                $count = count($sheetData);
                $common = new CommonService();
                for ($i = 2; $i <= $count; ++$i) {
                    $sampleId = $sheetData[$i]['A'];
                    if (isset($sheetData[$i]['A']) && trim($sheetData[$i]['A']) != '') {
                        $cQuery = $sql->select()->from('recency')->columns(array('recency_id'))
                            ->where(array('sample_id' => $sampleId));
                        $fQuery = $sql->buildSqlString($cQuery);
                        $fResult = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                        if (isset($fResult['recency_id'])) {
                            $data = array(
                                'vl_test_date' => date('Y-m-d', strtotime($sheetData[$i]['C'])),
                                'vl_result' => $sheetData[$i]['B'],
                                'upload_result_datetime' => date('Y-m-d h:i:s')
                            );
                            $recencyDb->update($data, array('recency_id' => $fResult['recency_id']));
                            // Add event log
                            $subject                = $fResult['recency_id'];
                            $eventType              = 'Upload VL Result File';
                            $action                 = 'Uploaded VL Result File ';
                            $resourceName           = 'Upload VL Result ';
                            $eventLogDb             = $this->sm->get('EventLogTable');
                            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                        }
                    }
                }
                unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName);
                $container = new Container('alert');
                $container->alertMsg = 'Result details uploaded successfully';
            }
        }
    }

    public function getLocationBasedFacility($params)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchLocationBasedFacility($params);
    }

    public function vlsmSync()
    {
        /** @var RecencyTable $recencyDb */
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->vlsmSync($this->sm);
    }

    public function getWeeklyReport($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->getWeeklyReport($params);
    }


    public function exportWeeklyReport($params)
    {

        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportWeeklyDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportWeeklyDataQuery);
            $result = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            $termOutcome = 0;
            $vlResult = 0;
            $finalResult = 0;
            //if(isset($result[0]['Samples Tested']) && $result[0]['Samples Tested']!=''){

            $totalSamples = $result[0]['Samples Pending to be Tested'] + $result[0]['Samples Tested'];
            $termOutcome = $result[0]['Assay Recent'] + $result[0]['Long Term'] + $result[0]['Assay Negative'];
            $vlResult = $result[0]['VL Done'] + $result[0]['VL Pending'];
            $finalResult = $result[0]['RITA Recent'] + $result[0]['Long Term Final'] + $result[0]['Inconclusive'];
            $row = array();
            $row[] = $result[0]['Samples Received'];
            $row[] = $result[0]['Samples Pending to be Tested'];
            $row[] = $result[0]['Samples Tested'];
            $row[] = $result[0]['Assay Recent'];
            $row[] = $result[0]['Long Term'];
            $row[] = $result[0]['Assay Negative'];
            $row[] = $result[0]['VL Done'];
            $row[] = $result[0]['VL Pending'];
            $row[] = $result[0]['RITA Recent'];
            $row[] = $result[0]['Long Term Final'];
            $row[] = $result[0]['Inconclusive'];
            $output[] = $row;

            //}

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THICK,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );

            $sheet->mergeCells('A1:A2');
            $sheet->mergeCells('B1:B2');
            $sheet->mergeCells('C1:C2');
            $sheet->mergeCells('D1:F1');
            $sheet->mergeCells('G1:H1');
            $sheet->mergeCells('I1:K1');

            $this->cellColor('C1:C2', '367fa9', $excel);

            $this->cellColor('D1:F1', 'ebed89', $excel);
            $this->cellColor('D2:F2', '9cc2e5', $excel);
            $this->cellColor('G1:H1', 'ebed89', $excel);
            $this->cellColor('G2:H2', '95b78d', $excel);
            $this->cellColor('I1:K1', '11aa06', $excel);
            //$this->cellColor('E2:F2', 'edda95',$excel);
            $this->cellColor('I2:K2', 'dce5ed', $excel);

            // cellColor('A7:I7', 'F28A8C');
            // cellColor('A17:I17', 'F28A8C');
            // cellColor('A30:Z30', 'F28A8C');



            $sheet->setCellValue('A1', html_entity_decode('Samples Received', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Samples Pending to be Tested', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('Samples Tested', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('Recency Testing Results(Asante)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('Assay Recent VL Results', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('Final Results', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D2', html_entity_decode('Assay Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E2', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F2', html_entity_decode('Assay Negative', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G2', html_entity_decode('VL Done', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H2', html_entity_decode('VL Pending', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I2', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J2', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K2', html_entity_decode('Inconclusive', ENT_QUOTES, 'UTF-8'));


            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1:A2')->applyFromArray($styleArray);
            $sheet->getStyle('A2:B2')->applyFromArray($styleArray);
            $sheet->getStyle('B2:C2')->applyFromArray($styleArray);
            $sheet->getStyle('C2:D2')->applyFromArray($styleArray);
            $sheet->getStyle('B1:D1')->applyFromArray($styleArray);
            $sheet->getStyle('B1:D2')->applyFromArray($styleArray);

            $sheet->getStyle('E1:F1')->applyFromArray($styleArray);
            $sheet->getStyle('D2:E2')->applyFromArray($styleArray);
            $sheet->getStyle('E2:F2')->applyFromArray($styleArray);
            $sheet->getStyle('E1:F2')->applyFromArray($styleArray);
            $sheet->getStyle('F2:G2')->applyFromArray($styleArray);
            $sheet->getStyle('G1:I1')->applyFromArray($styleArray);
            $sheet->getStyle('G1:I2')->applyFromArray($styleArray);
            $sheet->getStyle('G2:H2')->applyFromArray($styleArray);
            $sheet->getStyle('H2:I2')->applyFromArray($styleArray);
            $sheet->getStyle('I2:J2')->applyFromArray($styleArray);
            $sheet->getStyle('J2:K2')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "0";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 3);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            if (isset($result[0]['Samples Tested']) && $result[0]['Samples Tested'] != '') {
                $totalSamples = $result[0]['Samples Received'];
                $sheet->setCellValue('B4', html_entity_decode(($result[0]['Samples Pending to be Tested'] != '' && $result[0]['Samples Pending to be Tested'] != 0) ? round(($result[0]['Samples Pending to be Tested'] / $totalSamples) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('C4', html_entity_decode(($result[0]['Samples Tested'] != '' && $result[0]['Samples Tested'] != 0) ? round(($result[0]['Samples Tested'] / $totalSamples) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('D4', html_entity_decode(($result[0]['Assay Recent'] != '' && $result[0]['Assay Recent'] != 0) ? round(($result[0]['Assay Recent'] / $termOutcome) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('E4', html_entity_decode(($result[0]['Long Term'] != '' && $result[0]['Long Term'] != 0) ? round(($result[0]['Long Term'] / $termOutcome) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('F4', html_entity_decode(($result[0]['Assay Negative'] != '' && $result[0]['Assay Negative'] != 0) ? round(($result[0]['Assay Negative'] / $termOutcome) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('G4', html_entity_decode(($result[0]['Done'] != '' && $result[0]['VL Done'] != 0) ? round(($result[0]['VL Done'] / $vlResult) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('H4', html_entity_decode(($result[0]['Pending'] != '' && $result[0]['VL Pending'] != 0) ? round(($result[0]['VL Pending'] / $vlResult) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('I4', html_entity_decode(($result[0]['RITA Recent'] != '' && $result[0]['RITA Recent'] != 0) ? round(($result[0]['RITA Recent'] / $finalResult) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('J4', html_entity_decode(($result[0]['Long Term Final'] != '' && $result[0]['Long Term Final'] != 0) ? round(($result[0]['Long Term Final'] / $finalResult) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('K4', html_entity_decode(($result[0]['Inconclusive'] != '' && $result[0]['Inconclusive'] != 0) ? round(($result[0]['Inconclusive'] / $finalResult) * 100, 2) . "%" : 0, ENT_QUOTES, 'UTF-8'));
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Weekly-Report-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'Weekly report data-export';
            $action                 = 'Exported Weekly report data ';
            $resourceName           = 'Weekly report  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            error_log("GENERATE-RECENCY-EXPORT-WEEKLY-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());

            return "";
        }
    }

    public function cellColor($cells, $color, $excel)
    {
        $excel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
            'type' => Fill::FILL_SOLID,
            'startcolor' => array(
                'rgb' => $color
            )
        ));
    }

    public function getRecencyDetailsForPDF($recenyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetailsForPDF($recenyId, $this->sm);
    }

    public function getAllRecencyResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        if (isset($params['comingFrom']) && trim($params['comingFrom']) == 'district') {
            return $recencyDb->fetchDistrictWiseRecencyResult($params);
        } else {
            return $recencyDb->fetchAllRecencyResult($params);
        }
    }

    public function fetchExportRecencyData($params)
    {
        try {

            $queryContainer = new Container('query');

            $output = array();

            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportRecencyDataResultDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportRecencyDataResultDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (!empty($sResult)) {

                $excel = new Spreadsheet();
                $sheet = $excel->getActiveSheet();
                foreach ($sResult as $aRow) {

                    $row =  array();

                    $ltPercentage = $invalidPercentage = $inconlusivePercentage = $recentPercentage = "0 %";
                    if (trim($aRow['samplesFinalLongTerm']) != "") {
                        $ltPercentage = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    if (trim($aRow['ritaRecent']) != "") {
                        $recentPercentage = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    if (isset($aRow['samplesFinalInconclusive']) && !empty($aRow['samplesFinalInconclusive'])) {
                        $inconlusivePercentage = round((($aRow['samplesFinalInconclusive'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    $totalAssayTested = $aRow['samplesReceived'] - $aRow['samplesTestBacklog'];
                    if (isset($aRow['samplesInvalid']) && !empty($aRow['samplesInvalid']) && $totalAssayTested > 0) {
                        $invalidPercentage = round((($aRow['samplesInvalid'] / $totalAssayTested) * 100), 2) . '%';
                    }

                    $row[] = $aRow['facility_name'];
                    $row[] = $aRow['testing_facility_name'];
                    $row[] = $aRow['totalSamples'];
                    $row[] = $aRow['samplesReceived'];
                    $row[] = $aRow['samplesRejected'];
                    $row[] = $aRow['samplesTestBacklog'];
                    $row[] = $aRow['samplesTestVlPending'];
                    $row[] = $aRow['samplesTestedRecency'];
                    $row[] = $aRow['samplesTestedViralLoad'];
                    $row[] = $aRow['samplesFinalOutcome'];
                    $row[] = $aRow['printedCount'];
                    $row[] = $aRow['samplesFinalLongTerm'];
                    $row[] = $ltPercentage;
                    $row[] = $aRow['ritaRecent'];
                    $row[] = $recentPercentage;
                    $row[] = $aRow['samplesFinalInconclusive'];
                    $row[] = $inconlusivePercentage;
                    $row[] = $aRow['samplesInvalid'];
                    $row[] = $invalidPercentage;
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B1', html_entity_decode('Testing Site Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('No. of Samples Registered', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('No. of Samples Received at Hub', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('No. of Samples Rejected', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('No. of Samples Waiting For Testing', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('No. of Samples VL Pending', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H1', html_entity_decode('No. of Samples With Assay Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('No. of Samples Tested With Viral Load', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J1', html_entity_decode('No. of Samples With Final Outcome', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K1', html_entity_decode('No. of Results Printed', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L1', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('M1', html_entity_decode('Long Term (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('N1', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('O1', html_entity_decode('RITA Recent (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('P1', html_entity_decode('No. of Inconclusive', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('Q1', html_entity_decode('Inconclusive (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('R1', html_entity_decode('No. of Invalid', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('S1', html_entity_decode('Invalid (%)', ENT_QUOTES, 'UTF-8'));

            $sheet->getStyle('A1:S1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if ($field != 1 && (!isset($value) || empty($value))) {
                        $value = "0";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Facility-Recency-Results-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'Facility Recency report data-export';
            $action                 = 'Exported Facility Recency report data ';
            $resourceName           = 'Facility Recency report  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("RECENCY-DATA-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getRecencyAllDataCount($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyAllDataCount($params);
    }

    public function getFinalOutcomeChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchFinalOutcomeChart($params);
    }

    public function mapManageColumnsDetails($params)
    {
        $recencyDb = $this->sm->get('ManageColumnsMapTable');
        return $recencyDb->mapManageColumnsDetails($params);
    }

    public function getAllManagaColumnsDetails($userId)
    {
        $recencyDb = $this->sm->get('ManageColumnsMapTable');
        return $recencyDb->fetchAllManagaColumnsDetails($userId);
    }

    public function getTesterWiseFinalOutcomeChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchTesterWiseFinalOutcomeChart($params);
    }

    public function getTesterWiseInvalidChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchTesterWiseInvalidChart($params);
    }

    public function getFacilityWiseInvalidChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchFacilityWiseInvalidChart($params);
    }

    public function getLotChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchLotChart($params);
    }

    public function getRecentInfectionByGenderChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentInfectionByGenderChart($params);
    }

    public function getRecentInfectionByDistrictChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentInfectionByDistrictChart($params);
    }

    public function getRecencyLabActivityChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyLabActivityChart($params);
    }

    public function getRecentInfectionByAgeChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentInfectionByAgeChart($params);
    }

    public function getRecentViralLoadChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentViralLoadChart($params);
    }

    public function exportDistrictRecencyData($params)
    {
        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportDistrictwiseRecencyResult->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportDistrictwiseRecencyResult);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row =  array();
                    $ltPercentage = $invalidPercentage = $inconlusivePercentage = $recentPercentage = "0 %";
                    if (trim($aRow['samplesFinalLongTerm']) != "") {
                        $ltPercentage = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    if (isset($aRow['ritaRecent']) && !empty($aRow['ritaRecent'])) {
                        $recentPercentage = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    if (isset($aRow['samplesFinalInconclusive']) && !empty($aRow['samplesFinalInconclusive'])) {
                        $inconlusivePercentage = round((($aRow['samplesFinalInconclusive'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
                    }
                    if (isset($aRow['samplesInvalid']) && !empty($aRow['samplesInvalid'])) {
                        $invalidPercentage = round((($aRow['samplesInvalid'] / $aRow['samplesTestedRecency']) * 100), 2) . '%';
                    }

                    $row[] = $aRow['district_name'];
                    $row[] = $aRow['totalSamples'];
                    $row[] = $aRow['samplesReceived'];
                    $row[] = $aRow['samplesRejected'];
                    $row[] = $aRow['samplesTestBacklog'];
                    $row[] = $aRow['samplesTestVlPending'];
                    $row[] = $aRow['samplesTestedRecency'];
                    $row[] = $aRow['samplesTestedViralLoad'];
                    $row[] = $aRow['samplesFinalOutcome'];
                    $row[] = $aRow['printedCount'];
                    $row[] = $aRow['samplesFinalLongTerm'];
                    $row[] = $ltPercentage;
                    $row[] = $aRow['ritaRecent'];
                    $row[] = $recentPercentage;
                    $row[] = $aRow['samplesFinalInconclusive'];
                    $row[] = $inconlusivePercentage;
                    $row[] = $aRow['samplesInvalid'];
                    $row[] = $invalidPercentage;
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'borders' => array(
                    'outline' => array(
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('District Name', ENT_QUOTES, 'UTF-8'));

            $sheet->setCellValue('B1', html_entity_decode('No. of Samples Registered', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C1', html_entity_decode('No. of Samples Received at Hub', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D1', html_entity_decode('No. of Samples Rejected', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E1', html_entity_decode('No. of Samples Waiting For Testing', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F1', html_entity_decode('No. of Samples VL Pending', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G1', html_entity_decode('No. of Samples With Assay Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H1', html_entity_decode('No. of Samples Tested With Viral Load', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I1', html_entity_decode('No. of Samples With Final Outcome', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J1', html_entity_decode('No. of Results Printed', ENT_QUOTES, 'UTF-8'));

            $sheet->setCellValue('K1', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L1', html_entity_decode('Long Term (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('M1', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('N1', html_entity_decode('RITA Recent (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('O1', html_entity_decode('No. of Inconclusive', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('P1', html_entity_decode('Inconclusive (%)', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('Q1', html_entity_decode('No. of Invalid', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('R1', html_entity_decode('Invalid (%)', ENT_QUOTES, 'UTF-8'));

            $sheet->getStyle('A1:R1')->applyFromArray($styleArray);


            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if ($field != 0 && (!isset($value) || empty($value))) {
                        $value = "0";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 2);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }

            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'District-Recency-Results-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'District Recency report data-export';
            $action                 = 'Exported District Recency report data ';
            $resourceName           = 'District Recency report  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("DISTRICT-DATA-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getModalityWiseFinalOutcomeChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchModalityWiseFinalOutcomeChart($params);
    }

    public function getRecentInfectionBySexLineChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentInfectionBySexLineChart($params);
    }

    public function getDistrictWiseMissingViralLoadChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchDistrictWiseMissingViralLoadChart($params);
    }

    public function getModalityWiseMissingViralLoadChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchModalityWiseMissingViralLoadChart($params);
    }

    public function getRecentInfectionByMonthSexChart($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentInfectionByMonthSexChart($params);
    }


    public function getRecentDetailsForPDF($recenyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecentDetailsForPDF($recenyId);
    }
    public function getLTermDetailsForPDF($recenyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchLTermDetailsForPDF($recenyId);
    }


    public function UpdatePdfUpdatedDate($recencyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->UpdatePdfUpdatedDateDetails($recencyId);
    }

    public function UpdateMultiplePdfUpdatedDate($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->UpdateMultiplePdfUpdatedDateDetails($params);
    }

    public function addVlTestResultApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->saveVlTestResultApi($params);
    }

    public function postReqVlTestOnVlsmDetails($params)
    {
        try {
            $sessionLogin = new Container('credo');
            $data = array();
            $check = false;
            $recencyDb = $this->sm->get('RecencyTable');
            $client = new GuzzleHttp\Client();
            $configResult = $this->sm->get('Config');
            $urlVlsm = rtrim($configResult['vlsm']['domain'], "/") . '/recency/requestVlTest.php';
            if (isset($params['rvlsm']) && count($params['rvlsm']) > 0) {
                foreach ($params['rvlsm'] as $sample) {
                    $data =  $recencyDb->getDataBySampleId($sample);

                    if (isset($data['sample_id']) && $data['sample_id'] != "") {
                        $resultCart = $client->post($urlVlsm, [
                            'form_params' => [
                                'sampleId'            => (isset($data['sample_id']) && $data['sample_id'] != '') ? $data['sample_id'] : '',
                                'patientId'           => (isset($data['patient_id']) && $data['patient_id'] != '') ? $data['patient_id'] : '',
                                'isFacilityLab'       => (isset($params['isFacilityLab']) && $params['isFacilityLab'] != '') ? $params['isFacilityLab'] : '',
                                // 'province'            => $data['province'],
                                // 'district'            => $data['district'],
                                'sCDate'              => (isset($data['sample_collection_date']) && $data['sample_collection_date'] != '') ? $data['sample_collection_date'] : '',
                                // 'sampleType'          => $data['received_specimen_type'],
                                'isVlLab'             => (isset($params['isVlLab']) && $params['isVlLab'] != '') ? $params['isVlLab'] : '',
                                'userId'              => $sessionLogin->userId ?? '',
                                'dob'                 => (isset($data['dob']) && $data['dob'] != '') ? $data['dob'] : '',
                                'age'                 => (isset($data['age']) && $data['age'] != '') ? $data['age'] : '',
                                'gender'              => (isset($data['gender']) && $data['gender'] != '') ? $data['gender'] : '',
                                'service'             => ''
                            ]
                        ]);
                        $responseCart = $resultCart->getBody()->getContents();
                        if ($responseCart == 'success') {
                            $recencyDb->saveRequestFlag($data['recency_id']);
                            $check = true;
                        }
                    }
                }

                if ($check) {
                    $alertContainer = new Container('alert');
                    $alertContainer->alertMsg = 'VL Test requested successfully';
                }
            }
        } catch (Exception $e) {
            error_log('Error :' . $e->getMessage());
        }
    }

    public function getKitInfo($kitNo = "")
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchKitInfo($kitNo);
    }

    public function getModalityDetails($params = "")
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchModalityDetails($params);
    }

    public function exportModalityDetails($params)
    {
        try {
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();

            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->fetchModalityDetails($params);

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
                    ),
                ),
            );
            $horizontal = array('B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $ageArray = array('15-19', '20-24', '25-29', '30-34', '35-39', '40-44', '45-49', '50+');
            $sheet->setCellValue('A1', html_entity_decode('RTRI Results', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A2', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A3', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A7', html_entity_decode('Confirmed Results', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A8', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A9', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'));

            $sheet->setCellValue('A4', html_entity_decode('Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A5', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A10', html_entity_decode('Recent', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('A11', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'));
            /* Style and merge */
            $sheet->getStyle('A2:A11')->applyFromArray($borderStyle);
            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('A7')->applyFromArray($styleArray);
            $sheet->mergeCells('A1:Q1');
            $sheet->mergeCells('A7:Q7');

            /* Age cell creation */
            $rtrif = 2;
            $rtrim = 3;
            $index = 0;
            foreach ($ageArray as $age) {
                $sheet->setCellValue($horizontal[$index] . '2', html_entity_decode($age, ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue($horizontal[$index] . '8', html_entity_decode($age, ENT_QUOTES, 'UTF-8'));
                $sheet->getStyle($horizontal[$index] . '2')->applyFromArray($borderStyle);
                $sheet->getStyle($horizontal[$index] . '8')->applyFromArray($borderStyle);
                $index += 2;

                $sheet->mergeCells($horizontal[$rtrif] . '2:' . $horizontal[$rtrim] . '2');
                $sheet->mergeCells($horizontal[$rtrif] . '8:' . $horizontal[$rtrim] . '8');
                $rtrim += 2;
                $rtrif += 2;
            }
            $sheet->mergeCells('B2:C2');
            $sheet->mergeCells('B8:C8');
            /* Male Female cell creation */
            $index = 0;
            foreach (range(1, 16) as $x) {
                if ($x % 2 !== 0) {
                    $sheet->setCellValue($horizontal[$index] . '3', html_entity_decode('Female', ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[$index] . '9', html_entity_decode('Female', ENT_QUOTES, 'UTF-8'));
                } else {
                    $sheet->setCellValue($horizontal[$index] . '3', html_entity_decode('Male', ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[$index] . '9', html_entity_decode('Male', ENT_QUOTES, 'UTF-8'));
                }
                $sheet->getStyle($horizontal[$index] . '3')->applyFromArray($borderStyle);
                $sheet->getStyle($horizontal[$index] . '9')->applyFromArray($borderStyle);
                $index++;
            }
            /* Value cell creation start */
            $index = 0;
            foreach (range(1, 16) as $x) {
                $sheet->getStyle($horizontal[($x - 1)] . '4')->applyFromArray($borderStyle);
                $sheet->getStyle($horizontal[($x - 1)] . '5')->applyFromArray($borderStyle);
                if ($x % 2 !== 0) {
                    $sheet->setCellValue($horizontal[($x - 1)] . '4', html_entity_decode($result['rtriRecent' . $ageArray[$index] . 'F'], ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[($x - 1)] . '5', html_entity_decode($result['rtriLT' . $ageArray[$index] . 'F'], ENT_QUOTES, 'UTF-8'));
                } else {
                    $sheet->setCellValue($horizontal[($x - 1)] . '4', html_entity_decode($result['rtriRecent' . $ageArray[$index] . 'M'], ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[($x - 1)] . '5', html_entity_decode($result['rtriLT' . $ageArray[$index] . 'M'], ENT_QUOTES, 'UTF-8'));
                    $index++;
                }
            }

            $index = 0;
            foreach (range(1, 16) as $x) {
                $sheet->getStyle($horizontal[($x - 1)] . '10')->applyFromArray($borderStyle);
                $sheet->getStyle($horizontal[($x - 1)] . '11')->applyFromArray($borderStyle);
                if ($x % 2 !== 0) {
                    $sheet->setCellValue($horizontal[($x - 1)] . '10', html_entity_decode($result['confirmedRecent' . $ageArray[$index] . 'F'], ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[($x - 1)] . '11', html_entity_decode($result['confirmedLT' . $ageArray[$index] . 'F'], ENT_QUOTES, 'UTF-8'));
                } else {
                    $sheet->setCellValue($horizontal[($x - 1)] . '10', html_entity_decode($result['confirmedRecent' . $ageArray[$index] . 'M'], ENT_QUOTES, 'UTF-8'));
                    $sheet->setCellValue($horizontal[($x - 1)] . '11', html_entity_decode($result['confirmedLT' . $ageArray[$index] . 'M'], ENT_QUOTES, 'UTF-8'));
                    $index++;
                }
            }
            /* Value cell creation end */
            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Age-wise Infection Report-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            // Add event log
            $subject                = $filename;
            $eventType              = 'Age-wise Infection report data-export';
            $action                 = 'Exported Age-wise Infection report data ';
            $resourceName           = 'Age-wise Infection report  data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("Age-wise Infection Report-" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getPrintResultsDetails($parameters)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchPrintResultsDetails($parameters);
    }

    public function getRecencyDateBasedTestKit($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDateBasedTestKit($params);
    }
    public function checkPatientIdValidation($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->checkPatientIdValidation($params);
    }

    public function vlsmSendRequests()
    {
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $rResult = $recencyDb->getVlRequestSentOnYes();

            if (!empty($rResult)) {

                $client = new GuzzleHttp\Client();
                $configResult = $this->sm->get('Config');
                $authToken = $configResult['authToken'];
                $urlVlsm = rtrim($configResult['vlsm']['domain'], "/") . '/api/v1.1/vl/save-request.php';
                //echo $urlVlsm."     ";
                foreach ($rResult as $data) {
                    $dataArray = array();
                    $dataArray[] = [
                        'uniqueId'             => (isset($data['unique_id']) && $data['unique_id'] != '') ? $data['unique_id'] : '',
                        'appSampleCode'        => (isset($data['sample_id']) && $data['sample_id'] != '') ? $data['sample_id'] : '',
                        'sampleCollectionDate' => (isset($data['sample_collection_date']) && $data['sample_collection_date'] != '') ? $data['sample_collection_date'] : '',
                        'facilityId'           => (isset($data['facility_id']) && $data['facility_id'] != '') ? $data['facility_id'] : '',
                        'patientDob'           => (isset($data['dob']) && $data['dob'] != '') ? $data['dob'] : '',
                        'ageInYears'           => (isset($data['age']) && $data['age'] != '') ? $data['age'] : '',
                        'patientGender'        => (isset($data['gender']) && $data['gender'] != '') ? $data['gender'] : '',
                        'specimenType'         => (isset($data['received_specimen_type']) && $data['received_specimen_type'] != '') ? $data['received_specimen_type'] : '',
                        'provinceId'           => (isset($data['location_one']) && $data['location_one'] != '') ? $data['location_one'] : '',
                        'reasonForVLTesting'   => '9999',
                        'serialNo'             => (isset($data['sample_id']) && $data['sample_id'] != '') ? $data['sample_id'] : '',
                        'vlResult'             => (isset($data['vl_result']) && $data['vl_result'] != '') ? $data['vl_result'] : '',
                    ];
                    $params = array(
                        'appVersion' => 'v1.1',
                        'data' => $dataArray,
                    );
                    $requestData = json_encode($params, JSON_PRETTY_PRINT);
                    $dataArrayCount = count($dataArray);

                    $response = $client->request('POST', $urlVlsm, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $authToken,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $params,
                    ]);
                    //echo $response->getBody();
                    $statusValue = $response->getStatusCode();
                    $vlSampleCode = "";
                    $jsonResponse = $response->getBody();
                    $response = json_decode($jsonResponse, true);
                    if ($response != '') {
                        if ($statusValue == 200 && (isset($data['recency_id']) && $data['recency_id'] != "")) {
                            if ($response != '' && isset($response['data']) && $response['data'] != '') {
                                $responseData = $response['data'][0];
                                if ($responseData != '' && isset($responseData['sampleCode'])) {
                                    $vlSampleCode = $responseData['sampleCode'];
                                }
                            }
                            $recencyDb->updateVlRequestSentNO($data['recency_id'], $vlSampleCode);
                        }
                        $transactionId = $response['transactionId'] ?? null;
                        $userDb = $this->sm->get('UserTable');
                        $user = $userDb->fetchUserDetailsByauthToken($authToken);
                        $trackApiDb = $this->sm->get('TrackApiRequestsTable');
                        $trackApiDb->addApiTracking($transactionId, $user['user_id'], $dataArrayCount, 'save-request', 'vl', '/api/v1.1/vl/save-request.php', $requestData, $response, 'json');
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error :' . $e->getMessage());
        }
    }
    public function VlsmReceiveResults()
    {
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $client = new GuzzleHttp\Client();
            $configResult = $this->sm->get('Config');
            $authToken = $configResult['authToken'];
            $urlVlsm = rtrim($configResult['vlsm']['domain'], "/") . '/api/v1.1/vl/fetch-results.php';
            //echo $urlVlsm."     ";
            $rResult = $recencyDb->fetchPendingVlSampleData();
            $sampleCodes = array();
            $facilityIds = array();
            $uniqueIds = array();
            $sampleCollectionDates = array();
            $fromAndToDate = [];
            if (count($rResult) > 0) {
                foreach ($rResult as $row) {
                    if (isset($row['unique_id']) && $row['unique_id'] != '') {
                        $uniqueIds[] = $row['unique_id'];
                    }
                    if (isset($row['facility_id']) && $row['facility_id'] != '') {
                        $facilityIds[] = $row['facility_id'];
                    }
                    if (isset($row['sample_collection_date']) && $row['sample_collection_date'] != '') {
                        $sampleCollectionDates[] = $row['sample_collection_date'];
                    }
                    if (isset($row['lis_vl_sample_code']) && $row['lis_vl_sample_code'] != '') {
                        $sampleCodes[] = $row['lis_vl_sample_code'];
                    }
                }
            }
            if ($sampleCollectionDates !== []) {
                $fromDate = date('Y-m-d', strtotime(min($sampleCollectionDates)));
                $toDate = date('Y-m-d', strtotime(max($sampleCollectionDates)));
                $fromAndToDate[] = $fromDate;
                $fromAndToDate[] = $toDate;
            }
            if ($sampleCodes !== []) {
                $params = array(
                    'uniqueId'   => $uniqueIds,
                    'facility' => $facilityIds,
                    'sampleCode' => $sampleCodes,
                    'sampleCollectionDate' => $fromAndToDate
                );
                $requestData = json_encode($params, JSON_PRETTY_PRINT);

                $response = $client->request('GET', $urlVlsm, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $params,
                ]);
                $jsonResponse = $response->getBody();
                $response = json_decode($jsonResponse, true);

                if ($response != '' && isset($response['data']) && $response['data'] != '') {
                    $responseData = $response['data'];
                    if ($response['status'] === 'success') {
                        foreach ($responseData as $data) {
                            $finaloutcome = '';
                            if ((isset($data['vlResult']) && $data['vlResult'] > 1000) || (isset($data['vlResultCategory']) && $data['vlResultCategory'] == "not suppressed")) {
                                $finaloutcome = "RITA Recent";
                            }
                            if ((isset($data['vlResult']) && $data['vlResult'] <= 1000) || (isset($data['vlResultCategory']) && $data['vlResultCategory'] == "suppressed")) {
                                $finaloutcome = "Long Term";
                            }
                            if (isset($data['serialNo']) && $data['serialNo'] != '' && $data['serialNo'] != 'undefined' && $finaloutcome != '') {
                                $recencyDb->updatefinalOutComeBySampleId($data, $finaloutcome);
                            }
                        }
                        $transactionId = $response['transactionId'] ?? null;
                        $userDb = $this->sm->get('UserTable');
                        $user = $userDb->fetchUserDetailsByauthToken($authToken);
                        $trackApiDb = $this->sm->get('TrackApiRequestsTable');
                        $trackApiDb->addApiTracking($transactionId, $user['user_id'], count($responseData), 'fetch-results', 'vl', '/api/v1.1/vl/fetch-results.php', $requestData, $response, 'json');
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error :' . $e->getMessage());
        }
    }
    public function VlsmSampleStatus()
    {
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $client = new GuzzleHttp\Client();
            $configResult = $this->sm->get('Config');
            $authToken = $configResult['authToken'];
            $urlVlsm = rtrim($configResult['vlsm']['domain'], "/") . '/api/v1.1/sample-status.php';
            //echo $urlVlsm."     ";
            $rResult = $recencyDb->fetchPendingVlSampleData();
            $uniqueIds = array();
            $sampleCodes = array();
            if (count($rResult) > 0) {
                foreach ($rResult as $row) {
                    if (isset($row['unique_id']) && $row['unique_id'] != '') {
                        $uniqueIds[] = $row['unique_id'];
                    }
                    if (isset($row['lis_vl_sample_code']) && $row['lis_vl_sample_code'] != '') {
                        $sampleCodes[] = $row['lis_vl_sample_code'];
                    }
                }
            }
            if ($sampleCodes !== [] && $uniqueIds !== []) {
                $params = array(
                    'uniqueId'   => $uniqueIds,
                    'sampleCode' => $sampleCodes,
                    'testType' => 'vl',
                );
                $requestData = json_encode($params, JSON_PRETTY_PRINT);
                $response = $client->request('GET', $urlVlsm, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $params,
                ]);
                $jsonResponse = $response->getBody();
                $response = json_decode($jsonResponse, true);
                if ($response != '' && isset($response['data']) && $response['data'] != '') {
                    $responseData = $response['data'];
                    if ($response['status'] === 'success') {
                        foreach ($responseData as $data) {
                            if (isset($data['appSampleCode']) && $data['appSampleCode'] != '' && $data['appSampleCode'] != 'undefined') {
                                $recencyDb->updateSampleStatusBySampleId($data);
                            }
                        }
                        $transactionId = $response['transactionId'] ?? null;
                        $userDb = $this->sm->get('UserTable');
                        $user = $userDb->fetchUserDetailsByauthToken($authToken);
                        $trackApiDb = $this->sm->get('TrackApiRequestsTable');
                        $trackApiDb->addApiTracking($transactionId, $user['user_id'], count($responseData), 'sample-status', 'vl', '/api/v1.1/sample-status.php', $requestData, $response, 'json');
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Error :' . $e->getMessage());
        }
    }

    public function systemAlerts()
    {
        try {
            $recencyDb = $this->sm->get('RecencyTable');

            //Get empty vlResult for more than 30 days and Insert alert table
            $rResult = $recencyDb->getPendingVLResultAndInsertAlert();
            //Get empty  Recency Test for more than 30 days and Insert alert table
            $rResult = $recencyDb->getPendingRecencyTestAndInsertAlert();
        } catch (Exception $e) {
            error_log('Error :' . $e->getMessage());
        }
    }
}
