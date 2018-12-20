<?php
namespace Application\Service;

use Exception;
use PHPExcel;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;

class RecencyService
{

    public $sm = null;

    public function __construct($sm = null)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
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
                    $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);

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
            $sheet->setCellValue('D1', html_entity_decode('HIV Diagnosis Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E1', html_entity_decode('HIV Recency Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F1', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Positive Verification Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H1', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I1', html_entity_decode('Kit Lot Number', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J1', html_entity_decode('Kit Expiry Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K1', html_entity_decode('Term Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('L1', html_entity_decode('Final Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('M1', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('N1', html_entity_decode('Tester Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('O1', html_entity_decode('DOB', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('P1', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Q1', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('R1', html_entity_decode('Martial Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('S1', html_entity_decode('Residence', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('T1', html_entity_decode('Education Level', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('U1', html_entity_decode('Risk Population', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('V1', html_entity_decode('Pregnancy Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('W1', html_entity_decode('Current Sexual Partner', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('X1', html_entity_decode('Past HIV Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Y1', html_entity_decode('Last HIV Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Z1', html_entity_decode('Patient On ART', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AA1', html_entity_decode('Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AB1', html_entity_decode('Experienced Violence Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AC1', html_entity_decode('Form Initiation Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AD1', html_entity_decode('Form Transfer Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AE1', html_entity_decode('Form Saved Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AF1', html_entity_decode('Device ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AG1', html_entity_decode('Device Phone Number', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AH1', html_entity_decode('Data Added On', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AI1', html_entity_decode('Latitude', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AJ1', html_entity_decode('Longitude', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
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
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $aRow['facility_name'];
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
            $sheet->setCellValue('F1', html_entity_decode('Vl Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G1', html_entity_decode('Vl Test Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);

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
                    $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);
                    $row[] = $aRow['sample_id'];
                    $row[] = $aRow['term_outcome'];
                    $row[] = $aRow['final_outcome'];
                    $row[] = $aRow['facility_name'];
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


            //$sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);

            $sheet->getStyle('A1')->applyFromArray($styleArray);
            $sheet->getStyle('B1')->applyFromArray($styleArray);
            $sheet->getStyle('C1')->applyFromArray($styleArray);
            $sheet->getStyle('D1')->applyFromArray($styleArray);
            $sheet->getStyle('E1')->applyFromArray($styleArray);



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
}
