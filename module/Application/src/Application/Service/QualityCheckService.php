<?php

namespace Application\Service;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class QualityCheckService
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

    public function getQualityCheckDetails($params)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        $acl = $this->sm->get('AppAcl');
        return $qcTestDb->fetchQualityCheckDetails($params,$acl);
    }

    public function addQcTestDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $qcTestDb = $this->sm->get('QualityCheckTable');
            $result = $qcTestDb->addQualityCheckTestResultDetails($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Quality Check test details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Quality Check details-add';
                $action                 = 'Added  Quality Check details for sample id ' . ucwords($params['qcSampleId']);
                $resourceName           = 'Quality Check Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getQualityCheckDetailsById($qualityCheckId)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchQualityCheckTestDetailsById($qualityCheckId);
    }

    public function updateQualityCheckDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $qcTestDb = $this->sm->get('QualityCheckTable');
            $result = $qcTestDb->updateQualityCheckTestDetails($params);
            if ($result > 0) {
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Quality Check test details updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Quality Check details-edit';
                $action                 = 'Edited  Quality Check details for sample id ' . ucwords($params['qcSampleId']);
                $resourceName           = 'Quality Check Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getQcDetails($id)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchQcDetails($id);
    }

    public function addQualityCheckDataApi($params)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->addQualityCheckDetailsApi($params);
    }

    public function exportQcData($params)
    {
        try {
            $common = new CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();

            $output = array();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $queryContainer->exportQcDataQuery->reset('limit')->reset('offset');
            $sQueryStr = $sql->buildSqlString($queryContainer->exportQcDataQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = ucwords($aRow['qc_sample_id']);
                    $row[] = $common->humanDateFormat($aRow['qc_test_date']);
                    $row[] = str_replace("_", " ", ucwords($aRow['reference_result']));
                    $row[] = ucwords($aRow['kit_lot_no']);
                    $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
                    $row[] = ucwords($aRow['control_line']);
                    $row[] = ucwords($aRow['positive_verification_line']);
                    $row[] = ucwords($aRow['long_term_verification_line']);
                    $row[] = ucwords($aRow['term_outcome']);
                    $row[] = ucwords($aRow['tester_name']);
                    $row[] = ucwords($aRow['hiv_recency_test_date']);
                    $row[] = ucwords($aRow['facility_name']);
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
                )
            );

            $borderStyle = array(
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THIN,
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

            $sheet->setCellValue('A1', html_entity_decode('Quality Check Data', ENT_QUOTES, 'UTF-8'));

            $sheet->setCellValue('A3', html_entity_decode('QC Sample ID', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B3', html_entity_decode('QC Test Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C3', html_entity_decode('Reference Result', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D3', html_entity_decode('Kit Lot Number', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('E3', html_entity_decode('Kit Expiry Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('F3', html_entity_decode('Control Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('G3', html_entity_decode('Verification Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('H3', html_entity_decode('Long Term Line', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('I3', html_entity_decode('Assay Outcome', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('J3', html_entity_decode('Tester Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('K3', html_entity_decode('HIV Recency Test Date', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('L3', html_entity_decode('Testing Facility', ENT_QUOTES, 'UTF-8'));

            // $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);L

            $sheet->getStyle('A3:A4')->applyFromArray($styleArray);
            $sheet->getStyle('B3:B4')->applyFromArray($styleArray);
            $sheet->getStyle('C3:C4')->applyFromArray($styleArray);
            $sheet->getStyle('D3:D4')->applyFromArray($styleArray);
            $sheet->getStyle('E3:E4')->applyFromArray($styleArray);
            $sheet->getStyle('F3:F4')->applyFromArray($styleArray);
            $sheet->getStyle('G3:G4')->applyFromArray($styleArray);
            $sheet->getStyle('H3:H4')->applyFromArray($styleArray);
            $sheet->getStyle('I3:I4')->applyFromArray($styleArray);
            $sheet->getStyle('J3:J4')->applyFromArray($styleArray);
            $sheet->getStyle('K3:K4')->applyFromArray($styleArray);
            $sheet->getStyle('L3:L4')->applyFromArray($styleArray);

            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                foreach ($rowData as $field => $value) {
                    if (!isset($value) || empty($value)) {
                        $value = "";
                    }
                    $col = Coordinate::stringFromColumnIndex($colNo);
                    $row = ($rowNo + 5);
                    $sheet->getCell($col . $row)->setValue($value);
                    $colNo++;
                }
            }
            $writer = IOFactory::createWriter($excel, 'Xlsx');
            $filename = 'Recency-Quality-Check-Data-' . date('d-M-Y-H-i-s') . '.xlsx';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);

            // Add event log
            $subject                = $filename;
            $eventType              = 'Recency Quality Check data-export';
            $action                 = 'Exported Recency Quality Check data ';
            $resourceName           = 'Recency Quality Check data ';
            $eventLogDb             = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            return $filename;
            return $filename;
        } catch (Exception $exc) {
            error_log("RECENCY-QC-REPORT-" . $exc->getMessage());
            error_log($exc->getTraceAsString());
            return "";
        }
    }

    public function getQualityCheckVolumeChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchQualityCheckVolumeChart($params);
    }

    public function getQualityResultTermOutcomeChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchQualityResultTermOutcomeChart($params);
    }

    public function getKitLotNumberChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchKitLotNumberChart($params);
    }

    public function getSampleLotChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchSampleLotChart($params);
    }

    public function getTestingQualityNegativeChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchTestingQualityNegativeChart($params);
    }

    public function getTestingQualityInvalidChart($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchTestingQualityInvalidChart($params);
    }

    public function getPassedQualityBasedOnFacility($params)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchPassedQualityBasedOnFacility($params);
    }

    public function getMonthWiseQualityControlChart($params)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchMonthWiseQualityControlChart($params);
    }

    public function getDistrictWiseQualityCheckInvalid($params)
    {
        $qualityCheckDb = $this->sm->get('QualityCheckTable');
        return $qualityCheckDb->fetchDistrictWiseQualityCheckInvalid($params);
    }


    public function getQualityCheckReportDetails($parameters)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchQualityCheckReportDetails($parameters);
    }
}
