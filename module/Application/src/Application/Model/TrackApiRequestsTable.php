<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Application\Service\CommonService;
use ZipArchive;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author amit
 */
class TrackApiRequestsTable extends AbstractTableGateway
{

    protected $table = 'track_api_requests';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function addApiTracking($transactionId, $user, $numberOfRecords, $requestType, $testType, $url, $format, $requestData = null, $responseData = null)
    {

        $folderPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'track-api';
        if (!empty($requestData) && $requestData !== '[]') {
            if (!is_dir($folderPath . DIRECTORY_SEPARATOR . 'requests')) {
                mkdir($folderPath . DIRECTORY_SEPARATOR . 'requests', 0777, true);
            }
            $zip = new ZipArchive();
            $zipFileName = "$folderPath/requests/$transactionId.json.zip";

            if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
                $zip->addFromString("$transactionId.json", $requestData);
                $zip->close();
            }
        }

        $responseDataJson = json_encode($responseData, JSON_PRETTY_PRINT);

        if ($responseDataJson !== '' && $responseDataJson !== false && $responseDataJson !== '[]') {
            if (!is_dir($folderPath . DIRECTORY_SEPARATOR . 'responses')) {
                mkdir($folderPath . DIRECTORY_SEPARATOR . 'responses', 0777, true);
            }
            $zip = new ZipArchive();
            $zipFileName = "$folderPath/responses/$transactionId.json.zip";

            if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
                $zip->addFromString("$transactionId.json", $responseDataJson);
                $zip->close();
            }
        }

        $common = new CommonService();
        $currentDateTime = $common->getDateTime();
        $data = [
            'transaction_id' => $transactionId ?? null,
            'requested_by' => $user ?? 'system',
            'requested_on' => $currentDateTime,
            'number_of_records' => $numberOfRecords ?? 0,
            'request_type' => $requestType ?? null,
            'test_type' => $testType ?? null,
            'api_url' => $url ?? null,
            'data_format' => $format ?? null
        ];
        $id = $this->insert($data);
    }

    public function fetchAllTrackApiDetails($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new \Application\Service\CommonService();
        $aColumns = array('t.transaction_id', 't.number_of_records', 't.request_type', 't.test_type', 't.api_url', "DATE_FORMAT(t.requested_on,'%d-%b-%Y %g:%i %a')");
        $orderColumns = array('t.transaction_id', 't.number_of_records', 't.request_type', 't.test_type', 't.api_url', 't.requested_on');
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /*
         * Ordering
         */

        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                    $sOrder .= $orderColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
                }
            }
            $sOrder = substr_replace($sOrder, "", -1);
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = "";
        if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
            $searchArray = explode(" ", $parameters['sSearch']);
            $sWhereSub = "";
            foreach ($searchArray as $search) {
                if ($sWhereSub == "") {
                    $sWhereSub .= "(";
                } else {
                    $sWhereSub .= " AND (";
                }
                $colSize = count($aColumns);

                for ($i = 0; $i < $colSize; $i++) {
                    if ($i < $colSize - 1) {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }
        /* Individual column filtering */
        $counter = count($aColumns);

        /* Individual column filtering */
        for ($i = 0; $i < $counter; $i++) {
            if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                } else {
                    $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                }
            }
        }
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery =  $sql->select()->from(array('t' => 'track_api_requests'));

        $start_date = "";
        $end_date = "";
        if (isset($parameters['requestedOn']) && trim($parameters['requestedOn']) != '') {
            $s_c_date = explode("to", $_POST['requestedOn']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
            $sQuery = $sQuery->where(array("DATE(t.requested_on) >='" . $start_date . "'", "DATE(t.requested_on) <='" . $end_date . "'"));
        }


        if ($parameters['testType'] != '') {
            $sQuery->where(array('t.test_type' => trim($parameters['testType'])));
        }

        if ($parameters['syncType'] != '') {
            $sQuery->where(array('t.request_type' => base64_decode($parameters['syncType'])));
        }

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $fQuery = $sql->buildSqlString($sQuery);
        $aResultFilterTotal = $dbAdapter->query($fQuery, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iTotal = $this->select()->count();
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $date = explode(" ", $aRow['requested_on']);
            $dateTime = $common->humanDateFormat($date[0]);
            $time_in_12_hour_format  = date("g:i a", strtotime($date[1]));
            $row[] = $aRow['transaction_id'];
            $row[] = $aRow['number_of_records'];
            $row[] = $aRow['request_type'];
            $row[] = $aRow['test_type'];
            $row[] = $aRow['api_url'];
            $row[] = $dateTime . " " . $time_in_12_hour_format;
            $row[] = '<a href="javascript:void(0);" class="btn btn-success btn-xs" style="margin-right: 2px;" title="Result" onclick="showParams(\'' . base64_encode($aRow['api_track_id']) . '\');"> Show Params</a>';
            $output['aaData'][] = $row;
        }
        return $output;
    }
    public function fetchApiParamsDetails($params)
    {
        $userRequest = $userResponse = "{}";
        $data = [];
        if (isset($params['apiTrackId']) && $params['apiTrackId'] != '') {

            $apiTrackId = base64_decode($params['apiTrackId']);

            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $sQuery =  $sql->select()->from(array('t' => 'track_api_requests'))
                ->where(array('t.api_track_id' => $apiTrackId));
            $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
            $result = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();


            $folder = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . 'track-api');

            // Check if the requests directory exists
            $requestsDirectory = $folder . DIRECTORY_SEPARATOR . 'requests';

            if (is_dir($requestsDirectory)) {
                $requestFiles = glob($requestsDirectory . DIRECTORY_SEPARATOR . $result['transaction_id'] . '.json.zip');

                if ($requestFiles !== [] && $requestFiles !== false) {
                    $userRequestFile = reset($requestFiles);

                    $zip = new ZipArchive();
                    if ($zip->open($userRequestFile) === true) {
                        // Extract the contents of id.json from the zip archive
                        $userRequestContent = $zip->getFromName($result['transaction_id'] . '.json');
                        $zip->close();

                        if ($userRequestContent !== false) {
                            $userRequest = json_decode($userRequestContent, true);
                        }
                    }
                }
            }

            // Check if the responses directory exists
            $responsesDirectory = $folder . DIRECTORY_SEPARATOR . 'responses';
            if (is_dir($responsesDirectory)) {
                $responseFiles = glob($responsesDirectory . DIRECTORY_SEPARATOR . $result['transaction_id'] . '.json.zip');

                if ($responseFiles !== [] && $responseFiles !== false) {
                    $userResponseFile = reset($responseFiles);

                    $zip = new ZipArchive();
                    if ($zip->open($userResponseFile) === true) {
                        // Extract the contents of id.json from the zip archive
                        $userResponseContent = $zip->getFromName($result['transaction_id'] . '.json');
                        $zip->close();

                        if ($userResponseContent !== false) {
                            $userResponse = json_decode($userResponseContent, true);
                        }
                    }
                }
            }
        }
        return [
            'userRequest' => $userRequest,
            'userResponse' => $userResponse,
        ];
    }
}
