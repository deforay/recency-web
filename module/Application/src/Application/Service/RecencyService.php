<?php
namespace Application\Service;

use Exception;
use Zend\Mail;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use PHPExcel;

class RecencyService {

     public $sm = null;

     public function __construct($sm = null) {
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
               // \Zend\Debug\Debug::dump($result);die;
               if($result > 0){
                    $adapter->commit();

                    // $eventAction = 'Added a new Role Detail with the name as - '.ucwords($params['roleName']);
                    // $resourceName = 'Roles';
                    // $eventLogDb = $this->sm->get('EventLogTable');
                    // $eventLogDb->addEventLog($eventAction, $resourceName);
                    $alertContainer = new Container('alert');
                    $alertContainer->alertMsg = 'Recency details added successfully';
               }

          }
          catch (Exception $exc) {
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

     public function updateRecencyDetails($params){
          $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
          $adapter->beginTransaction();
          try {
               $recencyDb = $this->sm->get('RecencyTable');
               $result = $recencyDb->updateRecencyDetails($params);
               if($result > 0){
                    $adapter->commit();

                    // $eventAction = 'Updated Role Detail with the name as - '.ucwords($params['roleName']);
                    //  $resourceName = 'Roles';
                    //  $eventLogDb = $this->sm->get('EventLogTable');
                    //  $eventLogDb->addEventLog($eventAction, $resourceName);

                    $alertContainer = new Container('alert');
                    $alertContainer->alertMsg = 'Recency details updated successfully';
               }
          }
          catch (Exception $exc) {
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

     public function getTesterData($val) {
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
        try{
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
            if(count($sResult) > 0) {
                foreach($sResult as $aRow) {
                    $row = array();
                    $formInitiationDate = '';
                         if($aRow['form_initiation_datetime']!='' && $aRow['form_initiation_datetime']!='0000-00-00 00:00:00' && $aRow['form_initiation_datetime']!=NULL){
                            $formInitiationAry = explode(" ",$aRow['form_initiation_datetime']);
                            $formInitiationDate = $common->humanDateFormat($formInitiationAry[0])." ".$formInitiationAry[1];
                         }
                         $formTransferDate = '';
                         if($aRow['form_transfer_datetime']!='' && $aRow['form_transfer_datetime']!='0000-00-00 00:00:00' && $aRow['form_transfer_datetime']!=NULL){
                            $formTransferAry = explode(" ",$aRow['form_transfer_datetime']);
                            $formTransferDate = $common->humanDateFormat($formTransferAry[0])." ".$formTransferAry[1];
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
                         $row[] = str_replace("_"," ",ucwords($aRow['marital_status']));
                         $row[] = ucwords($aRow['residence']);
                         $row[] = ucwords($aRow['education_level']);
                         $row[] = ucwords($aRow['name']);
                         $row[] = str_replace("_"," ",ucwords($aRow['pregnancy_status']));
                         $row[] = str_replace("_","-",$aRow['current_sexual_partner']);
                         $row[] = ucwords($aRow['past_hiv_testing']);
                         $row[] = ucwords($aRow['last_hiv_status']);
                         $row[] = ucwords($aRow['patient_on_art']);
                         $row[] = ucwords($aRow['test_last_12_month']);
                         $row[] = ucwords($aRow['exp_violence_last_12_month']);
                         $row[] = $formInitiationDate;
                         $row[] = $formTransferDate;
                    $output[] = $row;
               }
            }
            
            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size'=>12,
                ),
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                )
            );
            
           $borderStyle = array(
                'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    ),
                )
            );
           
            $sheet->mergeCells('A1:B1');
            $sheet->mergeCells('A3:A4');
            $sheet->mergeCells('B3:B4');
            $sheet->mergeCells('C3:C4');
            $sheet->mergeCells('D3:D4');
            $sheet->mergeCells('E3:E4');
            $sheet->mergeCells('F3:F4');
            $sheet->mergeCells('G3:G4');
            $sheet->mergeCells('H3:H4');
            $sheet->mergeCells('I3:I4');
            $sheet->mergeCells('J3:J4');
            $sheet->mergeCells('K3:K4');
            $sheet->mergeCells('L3:L4');
            $sheet->mergeCells('M3:M4');
            $sheet->mergeCells('N3:N4');
            $sheet->mergeCells('O3:O4');
            $sheet->mergeCells('P3:P4');
            $sheet->mergeCells('Q3:Q4');
            $sheet->mergeCells('R3:R4');
            $sheet->mergeCells('S3:S4');
            $sheet->mergeCells('T3:T4');
            $sheet->mergeCells('U3:U4');
            $sheet->mergeCells('V3:V4');
            $sheet->mergeCells('W3:W4');
            $sheet->mergeCells('X3:X4');
            $sheet->mergeCells('Y3:Y4');
            $sheet->mergeCells('Z3:Z4');
            $sheet->mergeCells('AA3:AA4');
            $sheet->mergeCells('AB3:AB4');
            $sheet->mergeCells('AC3:AC4');
            $sheet->mergeCells('AD3:AD4');
            
            $sheet->setCellValue('A1', html_entity_decode('Recency Data', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
           
            $sheet->setCellValue('A3', html_entity_decode('Sample ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('B3', html_entity_decode('Patient ID', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('C3', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D3', html_entity_decode('HIV Diagnosis Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E3', html_entity_decode('HIV Recency Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('F3', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('G3', html_entity_decode('Positive Verification Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('H3', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('I3', html_entity_decode('Kit Lot Number', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('J3', html_entity_decode('Kit Expiry Date', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('K3', html_entity_decode('Term Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('L3', html_entity_decode('Final Outcome', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('M3', html_entity_decode('VL Result', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('N3', html_entity_decode('Tester Name', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('O3', html_entity_decode('DOB', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('P3', html_entity_decode('Age', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Q3', html_entity_decode('Gender', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('R3', html_entity_decode('Martial Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('S3', html_entity_decode('Residence', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('T3', html_entity_decode('Education Level', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('U3', html_entity_decode('Risk Population', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('V3', html_entity_decode('Pregnancy Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('W3', html_entity_decode('Current Sexual Partner', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('X3', html_entity_decode('Past HIV Testing', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Y3', html_entity_decode('Last HIV Status', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('Z3', html_entity_decode('Patient On ART', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AA3', html_entity_decode('Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AB3', html_entity_decode('Experienced Violence Last 12 Month', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AC3', html_entity_decode('Form Initiation Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('AD3', html_entity_decode('Form Transfer Datetime', ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
            
            
            
            $sheet->getStyle('A1:B1')->getFont()->setBold(TRUE)->setSize(16);
            
            $sheet->getStyle('A3:A4')->applyFromArray($styleArray);
            $sheet->getStyle('B3:B4')->applyFromArray($styleArray);
            $sheet->getStyle('C3:C4')->applyFromArray($styleArray);
            $sheet->getStyle('D3:D4')->applyFromArray($styleArray);
            $sheet->getStyle('E3:E4')->applyFromArray($styleArray);
            $sheet->getStyle('F3:F4')->applyFromArray($styleArray);
            $sheet->getStyle('G3:G4')->applyFromArray($styleArray);
            $sheet->getStyle('G3:G4')->applyFromArray($styleArray);
            $sheet->getStyle('H3:H4')->applyFromArray($styleArray);
            $sheet->getStyle('I3:I4')->applyFromArray($styleArray);
            $sheet->getStyle('J3:J4')->applyFromArray($styleArray);
            $sheet->getStyle('K3:K4')->applyFromArray($styleArray);
            $sheet->getStyle('L3:L4')->applyFromArray($styleArray);
            $sheet->getStyle('M3:M4')->applyFromArray($styleArray);
            $sheet->getStyle('N3:N4')->applyFromArray($styleArray);
            $sheet->getStyle('O3:O4')->applyFromArray($styleArray);
            $sheet->getStyle('P3:P4')->applyFromArray($styleArray);
            $sheet->getStyle('Q3:Q4')->applyFromArray($styleArray);
            $sheet->getStyle('R3:R4')->applyFromArray($styleArray);
            $sheet->getStyle('S3:S4')->applyFromArray($styleArray);
            $sheet->getStyle('T3:T4')->applyFromArray($styleArray);
            $sheet->getStyle('U3:U4')->applyFromArray($styleArray);
            $sheet->getStyle('V3:V4')->applyFromArray($styleArray);
            $sheet->getStyle('W3:W4')->applyFromArray($styleArray);
            $sheet->getStyle('X3:X4')->applyFromArray($styleArray);
            $sheet->getStyle('Y3:Y4')->applyFromArray($styleArray);
            $sheet->getStyle('Z3:Z4')->applyFromArray($styleArray);
            $sheet->getStyle('AA3:AA4')->applyFromArray($styleArray);
            $sheet->getStyle('AB3:AB4')->applyFromArray($styleArray);
            $sheet->getStyle('AC3:AC4')->applyFromArray($styleArray);
            $sheet->getStyle('AD3:AD4')->applyFromArray($styleArray);
            
            foreach ($output as $rowNo => $rowData) {
                $colNo = 0;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 5)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->getCellByColumnAndRow($colNo, $rowNo + 5)->setValueExplicit(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), \PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $rRowCount = $rowNo + 5;
                    $cellName = $sheet->getCellByColumnAndRow($colNo, $rowNo + 5)->getColumn();
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyleByColumnAndRow($colNo, $rowNo + 5)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }
	    
            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $filename = 'Recency-data' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            return $filename;
        }
        catch (Exception $exc) {
            return "";
            error_log("GENERATE-PAYMENT-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
     }
}
?>
