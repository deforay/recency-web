<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\TableGateway\AbstractTableGateway;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SystemAlertsTable extends AbstractTableGateway {

    protected $table = 'system_alerts';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function getAlertType()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('s' => 'system_alerts'))
        ->columns(array(new Expression('DISTINCT(alert_type)')));
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance 
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function fetchAllAlertsDetails($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new \Application\Service\CommonService();
        $aColumns = array('f1.facility_name','f2.facility_name','s.alert_text','s.alert_type','s.alert_status',"DATE_FORMAT(s.alerted_on,'%d-%b-%Y %g:%i %a')");
        $orderColumns = array('f1.facility_name','f2.facility_name','s.alert_text','s.alert_type','s.alert_status','s.alerted_on');
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
            for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
                if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search ) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
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
        $sQuery =  $sql->select()->from(array('s' => 'system_alerts'))
                    ->join(array('f1' => 'facilities'), 'f1.facility_id=s.facility_id', array('facility_name'))
                    ->join(array('f2' => 'facilities'), 'f2.facility_id=s.lab_id', array('lab_name' => 'facility_name'));

        $start_date = "";
        $end_date = "";
        if (isset($parameters['alertedOn']) && trim($parameters['alertedOn']) != '') {
            $s_c_date = explode("to", $_POST['alertedOn']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['alertedOn'] != '') {
            $sQuery = $sQuery->where(array("DATE(s.alerted_on) >='" . $start_date . "'", "DATE(s.alerted_on) <='" . $end_date . "'"));
        }
        

        if ($parameters['alertType'] != '') {
            $sQuery->where(array('s.alert_type' => trim($parameters['alertType'])));
        }

        if ($parameters['facilityName'] != '') {
            $sQuery->where(array('s.facility_id' => base64_decode($parameters['facilityName'])));
        }

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if ($sessionLogin->facilityMap != null && $parameters['facilityName'] == '') {
            $sQuery = $sQuery->where('s.facility_id IN (' . $sessionLogin->facilityMap . ') OR s.lab_id IN (' . $sessionLogin->facilityMap . ')');
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
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $status = '<select class="form-control" name="status[]" id="' . $aRow['alert_id'] . '" title="' . _("Please select status") . '" onchange="updateStatus(this,\'' . $aRow['alert_status'] . '\')">
            <option value="Pending" ' . ($aRow['alert_status'] == "Pending" ? "selected=selected" : "") . '>' . _("Pending") . '</option>
            <option value="Resolved" ' . ($aRow['alert_status'] == "Resolved" ? "selected=selected" : "") . '>' . _("Resolved") . '</option>
            <option value="Ignore" ' . ($aRow['alert_status'] == "Ignore" ? "selected=selected" : "") . '>' . _("Ignore") . '</option>
            <option value="Expired" ' . ($aRow['alert_status'] == "Expired" ? "selected=selected" : "") . '>' . _("Expired") . '</option>
            </select>';

            $alertType = '';
            if($aRow['alert_type'] == 1){
                $alertType = "Critical";
            }elseif($aRow['alert_type'] == 2){
                $alertType = "Warning";
            }elseif($aRow['alert_type'] == 3){
                $alertType = "Error";
            }elseif($aRow['alert_type'] == 4){
                $alertType = "Failure";
            }elseif($aRow['alert_type'] == 5){
                $alertType = "Informational";
            }

            $date = explode(" ",$aRow['alerted_on']);
            $dateTime = $common->humanDateFormat($date[0]);
            $time_in_12_hour_format  = date("g:i a", strtotime($date[1]));
            $row[] = ucfirst($aRow['facility_name']);
            $row[] = ucfirst($aRow['lab_name']);
            $row[] = $aRow['alert_text'];
            $row[] = $alertType;
            $row[] = $status;
            $row[] = $dateTime." ".$time_in_12_hour_format;
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function UpdateAlertStatus($parameters) {
        $common = new \Application\Service\CommonService();
        $status = array(
            'alert_status' => $parameters['status'],
            'updated_datetime'     =>  $common->getDateTime(),
        );
        $this->update($status, array('alert_id' => $parameters['id']));
    }
}