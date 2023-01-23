<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Application\Service\CommonService;
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
class EventLogTable extends AbstractTableGateway {

    protected $table = 'event_log';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    public function addEventLog($subject, $eventType, $action, $resourceName) {
            $logincontainer = new Container('credo');
            $actor_id = $logincontainer->userId;
            $common = new CommonService();
            $currentDateTime=$common->getDateTime();
            $data = array('actor'=>$actor_id,
                          'subject'=>$subject,
                          'event_type'=>$eventType,
                          'action'=>$action,
                          'resource_name'=>$resourceName,
                          'added_on'=> $currentDateTime
                        );
            $id = $this->insert($data);
    }
    public function getRecentActivities($parameters) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $common = new \Application\Service\CommonService();
        $aColumns = array('u.user_name','e_l.action',"DATE_FORMAT(e_l.added_on,'%d-%b-%Y %g:%i %a')");
        $orderColumns = array('u.user_name','e_l.action','e_l.added_on');
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

        /*
         * SQL queries
         * Get data to display
         */
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('e_l' => 'event_log'))
                ->join(array('u' => 'users'), 'u.user_id=e_l.actor', array('user_name'));
        $start_date = "";
        $end_date = "";
        if (isset($parameters['addedOn']) && trim($parameters['addedOn']) != '') {
            $s_c_date = explode("to", $_POST['addedOn']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['addedOn'] != '') {
            $sQuery = $sQuery->where(array("DATE(e_l.added_on) >='" . $start_date . "'", "DATE(e_l.added_on) <='" . $end_date . "'"));
        }
     

        if ($parameters['action'] != '') {
            $sQuery->where(array('event_type' => trim($parameters['action'])));
        }

        if ($parameters['users'] != '') {
            $sQuery->where(array('actor' => trim($parameters['users'])));
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
       // echo $sQueryStr;die;
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
            $common = new \Application\Service\CommonService();
            $date = explode(" ",$aRow['added_on']);
            $dateTime = $common->humanDateFormat($date[0]);
            $time_in_12_hour_format  = date("g:i a", strtotime($date[1]));
            $row[] = ucfirst($aRow['user_name']);
            $row[] = $aRow['event_type'];
            $row[] = $aRow['action'];
            $row[] = $dateTime." ".$time_in_12_hour_format;
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function getEventType()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('e_l' => 'event_log'))
        ->columns(array(new Expression('DISTINCT(event_type)')));
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance 
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }
}
?>