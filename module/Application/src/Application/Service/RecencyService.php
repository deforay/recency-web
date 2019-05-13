<?php
namespace Application\Service;

use Exception;
use PHPExcel;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use TCPDF;

class RecencyService
{

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getRecencyDetails($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetails($params);
    }

    public function addRecencyDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->addRecencyDetails($params);
            if ($result > 0) {
                $adapter->commit();

                // $eventAction = 'Added a new Role Detail with the name as - '.ucwords($params['roleName']);
                // $resourceName = 'Roles';
                // $eventLogDb = $this->sm->get('EventLogTable');
                // $eventLogDb->addEventLog($eventAction, $resourceName);
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Recency details added successfully';
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

    public function updateRecencyDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->updateRecencyDetails($params);
            if ($result > 0) {
                $adapter->commit();

                // $eventAction = 'Updated Role Detail with the name as - '.ucwords($params['roleName']);
                //  $resourceName = 'Roles';
                //  $eventLogDb = $this->sm->get('EventLogTable');
                //  $eventLogDb->addEventLog($eventAction, $resourceName);

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
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyListApi($params);
    }

    public function getAllRecencyResultWithVlListApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyResultWithVlListApi($params);
    }

    public function getAllPendingVlResultListApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllPendingVlResultListApi($params);
    }

    public function addRecencyDataApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->addRecencyDetailsApi($params);
    }

    public function getRecencyOrderDetails($id)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyOrderDetails($id);
    }

    public function getTesterData($val)
    {
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

    public function exportRecencyData($params)
    {
        try {
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportRecencyDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($sResult) > 0) {

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
                    $row[] = ucwords($aRow['facility_name']);
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);

                    $row[] = ucwords($aRow['control_line']);
                    $row[] = ucwords($aRow['positive_verification_line']);
                    $row[] = ucwords($aRow['long_term_verification_line']);
                    $row[] = $aRow['kit_lot_no'];
                    $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $aRow['vl_result'];
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

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('Sample ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Patient ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('HIV Diagnosis Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('HIV Recency Test Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('Positive Verification Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J1', html_entity_decode('Kit Lot Number', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K1', html_entity_decode('Kit Expiry Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('L1', html_entity_decode('Assay Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('M1', html_entity_decode('Final Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('N1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('O1', html_entity_decode('Tester Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('P1', html_entity_decode('DOB', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Q1', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('R1', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('S1', html_entity_decode('Martial Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('T1', html_entity_decode('Residence', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('U1', html_entity_decode('Education Level', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('V1', html_entity_decode('Risk Population', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('W1', html_entity_decode('Pregnancy Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('X1', html_entity_decode('Current Sexual Partner', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Y1', html_entity_decode('Past HIV Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Z1', html_entity_decode('Last HIV Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AA1', html_entity_decode('Patient On ART', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AB1', html_entity_decode('Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AC1', html_entity_decode('Experienced Violence Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AD1', html_entity_decode('Form Initiation Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AE1', html_entity_decode('Form Transfer Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AF1', html_entity_decode('Form Saved Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AG1', html_entity_decode('Device ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AH1', html_entity_decode('Device Phone Number', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AI1', html_entity_decode('Data Added On', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AJ1', html_entity_decode('Latitude', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AK1', html_entity_decode('Longitude', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);
            $sheet->getStyle('H1')->applyFromArray($styleArray);
            $sheet->getStyle('I1')->applyFromArray($styleArray);
            $sheet->getStyle('J1')->applyFromArray($styleArray);
            $sheet->getStyle('K1')->applyFromArray($styleArray);
            $sheet->getStyle('L1')->applyFromArray($styleArray);
            $sheet->getStyle('M1')->applyFromArray($styleArray);
            $sheet->getStyle('N1')->applyFromArray($styleArray);
            $sheet->getStyle('O1')->applyFromArray($styleArray);
            $sheet->getStyle('P1')->applyFromArray($styleArray);
            $sheet->getStyle('Q1')->applyFromArray($styleArray);
            $sheet->getStyle('R1')->applyFromArray($styleArray);
            $sheet->getStyle('S1')->applyFromArray($styleArray);
            $sheet->getStyle('T1')->applyFromArray($styleArray);
            $sheet->getStyle('U1')->applyFromArray($styleArray);
            $sheet->getStyle('V1')->applyFromArray($styleArray);
            $sheet->getStyle('W1')->applyFromArray($styleArray);
            $sheet->getStyle('X1')->applyFromArray($styleArray);
            $sheet->getStyle('Y1')->applyFromArray($styleArray);
            $sheet->getStyle('Z1')->applyFromArray($styleArray);
            $sheet->getStyle('AA1')->applyFromArray($styleArray);
            $sheet->getStyle('AB1')->applyFromArray($styleArray);
            $sheet->getStyle('AC1')->applyFromArray($styleArray);
            $sheet->getStyle('AD1')->applyFromArray($styleArray);
            $sheet->getStyle('AE1')->applyFromArray($styleArray);
            $sheet->getStyle('AF1')->applyFromArray($styleArray);
            $sheet->getStyle('AG1')->applyFromArray($styleArray);
            $sheet->getStyle('AH1')->applyFromArray($styleArray);
            $sheet->getStyle('AI1')->applyFromArray($styleArray);
            $sheet->getStyle('AJ1')->applyFromArray($styleArray);
            $sheet->getStyle('AK1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Recency-Data-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getAllRecencyResultWithVlList($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyResultWithVlList($params);
    }

    public function getAllLtResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllLtResult($params);
    }

    // Export Result for Recent Infected :

    public function exportRInfectedData($params)
    {

        try {
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportRecentResultDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = $aRow['sample_id'];
                    $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
                    $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
                    $row[] = ucwords(str_replace('_', ' ', $aRow['received_specimen_type']));
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $aRow['facility_name'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $aRow['vl_result'];
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
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('Date Of Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('Sample Collection Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('Sample Receipt Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('Received Specimen Type', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('Assasy Test Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K1', html_entity_decode('VL Test Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);
            $sheet->getStyle('H1')->applyFromArray($styleArray);
            $sheet->getStyle('I1')->applyFromArray($styleArray);
            $sheet->getStyle('J1')->applyFromArray($styleArray);
            $sheet->getStyle('K1')->applyFromArray($styleArray);


            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Recent-Infections-Data-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    // All Long Term Infected Data:

    public function exportLongTermInfected($params)
    {

        try {
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportLongtermDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $aRow['facility_name'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $aRow['vl_result'];
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
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );


            $sheet->setCellValue('A1', html_entity_decode('Date Of Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('Assasy Test Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('VL Test Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);


            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
           $sheet->getStyle('G1')->applyFromArray($styleArray);
           $sheet->getStyle('H1')->applyFromArray($styleArray);




            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Long-term-Infection-Data-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
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
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportTatQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $aRow['sample_id'];
                    $row[] = ucwords($aRow['testing_facility_name']);
                    $row[] = $aRow['final_outcome'];
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                    $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                    $row[] = date('d-M-Y',strtotime($aRow['vl_result_entry_date']));
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
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );


            $sheet->setCellValue('A1', html_entity_decode('Sample Id', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Testing Site', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('Final Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('Recency Testing Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('VL Tested Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('VL Entered Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Difference(TAT)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);


            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'TAT-Report-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
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
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->updateEmailSendResult($params);
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
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        $sql = new Sql($dbAdapter);
        $allowedExtensions = array('xls', 'xlsx', 'csv');
            $fileName = preg_replace('/[^A-Za-z0-9.]/', '-', $_FILES['fileName']['name']);
            $fileName = str_replace(" ", "-", $fileName);
            $ranNumber = str_pad(rand(0, pow(10, 6)-1), 6, '0', STR_PAD_LEFT);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileName =$ranNumber.".".$extension;
            if (in_array($extension, $allowedExtensions)) {
            if (!file_exists(UPLOAD_PATH) && !is_dir(UPLOAD_PATH)) {
                mkdir(APPLICATION_PATH . DIRECTORY_SEPARATOR . "uploads");
            }
            
            if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName)) {
               
                if (move_uploaded_file($_FILES['fileName']['tmp_name'], UPLOAD_PATH .  DIRECTORY_SEPARATOR . $fileName)) {
                    $objPHPExcel = \PHPExcel_IOFactory::load(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName);
                    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                    $count = count($sheetData);
                    $common = new \Application\Service\CommonService();
                    for ($i = 2; $i <= $count; ++$i) {
                        $sampleId = $sheetData[$i]['A'];
                        if(isset($sheetData[$i]['A']) && trim($sheetData[$i]['A']) != '') {
                            $cQuery = $sql->select()->from('recency')->columns(array('recency_id'))
                                        ->where(array('sample_id'=>$sampleId));
                            $fQuery = $sql->getSqlStringForSqlObject($cQuery);
                            $fResult = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                            if(isset($fResult['recency_id']))
                            {
                                $data = array(
                                    'vl_test_date' => date('Y-m-d',strtotime($sheetData[$i]['C'])),
                                    'vl_result' => $sheetData[$i]['B'],
                                    'upload_result_datetime' => date('Y-m-d h:i:s')
                                );
                                $recencyDb->update($data,array('recency_id'=>$fResult['recency_id']));
                            }
                        }
                    }
                    unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . $fileName);
                    $container = new Container('alert');
                    $container->alertMsg = 'Result details uploaded successfully';
                }
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
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportWeeklyDataQuery);
            $result = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            $termOutcome = 0;
            $vlResult = 0;
            $finalResult = 0;
            //if(isset($result[0]['Samples Tested']) && $result[0]['Samples Tested']!=''){

                    $totalSamples = $result[0]['Samples Pending to be Tested'] + $result[0]['Samples Tested'];
                    $termOutcome = $result[0]['Assay Recent'] + $result[0]['Long Term'] + $result[0]['Assay Negative'];
                    $vlResult = $result[0]['Done'] + $result[0]['Pending'];
                    $finalResult = $result[0]['RITA Recent'] + $result[0]['Long Term Final'] + $result[0]['Inconclusive'];
                    $row = array();
                    $row[] = $result[0]['Samples Received'];
                    $row[] = $result[0]['Samples Pending to be Tested'];
                    $row[] = $result[0]['Samples Tested'];
                    $row[] = $result[0]['Assay Recent'];
                    $row[] = $result[0]['Long Term'];
                    $row[] = $result[0]['Assay Negative'];
                    $row[] = $result[0]['Done'];
                    $row[] = $result[0]['Pending'];
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
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THICK,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_MEDIUM,
                    ),
                ),
            );

            $sheet->mergeCells('A1:A2');
            $sheet->mergeCells('B1:B2');
            $sheet->mergeCells('C1:C2');
            $sheet->mergeCells('D1:F1');
            $sheet->mergeCells('G1:H1');
            $sheet->mergeCells('I1:K1');
             
            $this->cellColor('C1:C2', '367fa9',$excel);
            
            $this->cellColor('D1:F1', 'ebed89',$excel);
            $this->cellColor('D2:F2', '9cc2e5',$excel);
            $this->cellColor('G1:H1', 'ebed89',$excel);
            $this->cellColor('G2:H2', '95b78d',$excel);
            $this->cellColor('I1:K1', '11aa06',$excel);
            //$this->cellColor('E2:F2', 'edda95',$excel);
            $this->cellColor('I2:K2', 'dce5ed',$excel);
                
                // cellColor('A7:I7', 'F28A8C');
                // cellColor('A17:I17', 'F28A8C');
                // cellColor('A30:Z30', 'F28A8C');
            
                

            $sheet->setCellValue('A1', html_entity_decode('Samples Received', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Samples Pending to be Tested', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('Samples Tested', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('Recency Testing Results(Asante)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Assay Recent VL Results', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('Final Results', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D2', html_entity_decode('Assay Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E2', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F2', html_entity_decode('Assay Negative', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G2', html_entity_decode('Done', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H2', html_entity_decode('Pending', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I2', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J2', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K2', html_entity_decode('Inconclusive', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);


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

            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 3)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 3)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 3;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 3)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 6)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }
            if(isset($result[0]['Samples Tested']) && $result[0]['Samples Tested']!=''){
                $totalSamples = $result[0]['Samples Received'];
                $sheet->setCellValue('B4', html_entity_decode(($result[0]['Samples Pending to be Tested']!='' && $result[0]['Samples Pending to be Tested']!=0) ? round(($result[0]['Samples Pending to be Tested'] / $totalSamples) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('C4', html_entity_decode(($result[0]['Samples Tested']!='' && $result[0]['Samples Tested']!=0) ? round(($result[0]['Samples Tested'] / $totalSamples) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D4', html_entity_decode(($result[0]['Assay Recent']!='' && $result[0]['Assay Recent']!=0) ? round(($result[0]['Assay Recent'] / $termOutcome) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('E4', html_entity_decode(($result[0]['Long Term']!='' && $result[0]['Long Term']!=0) ? round(($result[0]['Long Term'] / $termOutcome) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('F4', html_entity_decode(($result[0]['Assay Negative']!='' && $result[0]['Assay Negative']!=0) ? round(($result[0]['Assay Negative'] / $termOutcome) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('G4', html_entity_decode(($result[0]['Done']!='' && $result[0]['Done']!=0) ? round(($result[0]['Done'] / $vlResult) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('H4', html_entity_decode(($result[0]['Pending']!='' && $result[0]['Pending']!=0) ? round(($result[0]['Pending'] / $vlResult) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('I4', html_entity_decode(($result[0]['RITA Recent']!='' && $result[0]['RITA Recent']!=0)?round(($result[0]['RITA Recent'] / $finalResult) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('J4', html_entity_decode(($result[0]['Long Term Final']!='' && $result[0]['Long Term Final']!=0) ? round(($result[0]['Long Term Final'] / $finalResult) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('K4', html_entity_decode(($result[0]['Inconclusive']!='' && $result[0]['Inconclusive']!=0)? round(($result[0]['Inconclusive'] / $finalResult) * 100,2)."%":0, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Weekly-Report-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    function cellColor($cells,$color,$excel){
        $excel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
            'startcolor' => array(
                 'rgb' => $color
            )
        ));
    }

    public function getRecencyDetailsForPDF($recenyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetailsForPDF($recenyId);
    }
    
    public function getAllRecencyResult($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        if(isset($params['comingFrom']) && trim($params['comingFrom'])=='district'){
            return $recencyDb->fetchDistrictWiseRecencyResult($params);
        }else{
            return $recencyDb->fetchAllRecencyResult($params);
        }
        
    }

    public function fetchExportRecencyData($params)
    {
        try {
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportRecencyDataResultDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $ltper=0;
                    $arper=0;
                    $row = array();
                    if(trim($aRow['samplesFinalLongTerm'])!=""){
                        $ltper=round((($aRow['samplesFinalLongTerm']/$aRow['samplesFinalOutcome'])*100),2)."%";
                    }
                    if(trim($aRow['ritaRecent'])!=""){
                        $arper=round((($aRow['ritaRecent']/$aRow['samplesFinalOutcome'])*100),2)."%";
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
                         $row[] = $aRow['samplesFinalLongTerm'];
                         $row[] = $ltper;
                         $row[] = $aRow['ritaRecent'];
                         $row[] = $arper;
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B1', html_entity_decode('Testing Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('No. of Samples Registered', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('No. of Samples Received at Hub', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('No. of Samples Rejected', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('No. of Samples Waiting For Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('No. of Samples VL Pending', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('No. of Samples With Assay Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('No. of Samples Tested With Viral Load', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J1', html_entity_decode('No. of Samples With Final Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValue('K1', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('L1', html_entity_decode('Long Term (%)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('M1', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('N1', html_entity_decode('RITA Recent (%)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            
            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);
            $sheet->getStyle('H1')->applyFromArray($styleArray);
            $sheet->getStyle('I1')->applyFromArray($styleArray);
            $sheet->getStyle('J1')->applyFromArray($styleArray);
            $sheet->getStyle('K1')->applyFromArray($styleArray);
            $sheet->getStyle('L1')->applyFromArray($styleArray);
            $sheet->getStyle('M1')->applyFromArray($styleArray);
            $sheet->getStyle('N1')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Facility-Recency-Results-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
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
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new PHPExcel();
            $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array('memoryCacheSize' => '80MB');
            \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);

            $sQueryStr = $sql->getSqlStringForSqlObject($queryContainer->exportDistrictwiseRecencyResult);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $ltper=0;
                    $arper=0;
                    $row = array();
                    if(trim($aRow['samplesFinalLongTerm'])!=""){
                        $ltper=round((($aRow['samplesFinalLongTerm']/$aRow['samplesFinalOutcome'])*100),2).'%';
                    }
                    if(trim($aRow['ritaRecent'])!=""){
                        $arper=round((($aRow['ritaRecent']/$aRow['samplesFinalOutcome'])*100),2).'%';
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
                         $row[] = $aRow['samplesFinalLongTerm'];
                         $row[] = $ltper;
                         $row[] = $aRow['ritaRecent'];
                         $row[] = $arper;
                    $output[] = $row;
                }
            }

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $borderStyle = array(
                'alignment' => array(
                    //'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                ),
            );

            $sheet->setCellValue('A1', html_entity_decode('District Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            
            $sheet->setCellValue('B1', html_entity_decode('No. of Samples Registered', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C1', html_entity_decode('No. of Samples Received at Hub', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D1', html_entity_decode('No. of Samples Rejected', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('No. of Samples Waiting For Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('No. of Samples VL Pending', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('No. of Samples With Assay Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('No. of Samples Tested With Viral Load', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('No. of Samples With Final Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValue('J1', html_entity_decode('Long Term', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K1', html_entity_decode('Long Term (%)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('L1', html_entity_decode('RITA Recent', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('M1', html_entity_decode('RITA Recent (%)', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            
            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);
            $sheet->getStyle('F1')->applyFromArray($styleArray);
            $sheet->getStyle('G1')->applyFromArray($styleArray);
            $sheet->getStyle('H1')->applyFromArray($styleArray);
            $sheet->getStyle('I1')->applyFromArray($styleArray);
            $sheet->getStyle('J1')->applyFromArray($styleArray);
            $sheet->getStyle('K1')->applyFromArray($styleArray);
            $sheet->getStyle('L1')->applyFromArray($styleArray);
            $sheet->getStyle('M1')->applyFromArray($styleArray);
            

            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 2;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 2)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'District-Recency-Results-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
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
}

