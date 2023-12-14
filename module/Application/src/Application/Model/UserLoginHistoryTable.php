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
 * @author Jeyabanu
 */
class UserLoginHistoryTable extends AbstractTableGateway
{

    protected $table = 'user_login_history';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function addUserLoginHistory($params, $status)
    {
        $logincontainer = new Container('credo');
        $ipaddress = '';
        $browserAgent = $_SERVER['HTTP_USER_AGENT'];
        $os = PHP_OS;
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        $common = new CommonService();
        $currentDateTime = $common->getDateTime();
        $loginData = array(
            'user_id' => $logincontainer->userId,
            'login_id' => $params['userName'],
            'login_attempted_datetime' => $currentDateTime,
            'login_status' => $status,
            'ip_address' => $ipaddress,
            'browser' => $browserAgent,
            'operating_system' => $os,
        );
        $this->insert($loginData);
    }

    public function fetchUserLoginHistoryDetails($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $common = new CommonService();
        $sessionLogin = new Container('credo');
        $aColumns = array('u.login_attempted_datetime', 'us.user_name', 'u.login_id', 'u.ip_address', 'u.browser', 'u.operating_system', 'u.login_status');
        $orderColumns = array('u.login_attempted_datetime', 'us.user_name', 'u.login_id', 'u.ip_address', 'u.browser', 'u.operating_system', 'u.login_status');

        /* Paging */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /* Ordering */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                    $sOrder .= $aColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        /*
          * SQL queries
          * Get data to display
          */
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();

        $sQuery = $sql->select()->from(array('u' => 'user_login_history'))
            ->join(array('us' => 'users'), 'us.user_id = u.user_id', array('user_name'), 'left');

        if (isset($parameters['user']) && $sessionLogin->userId != "") {
            $sQuery->where(array('u.user_id' => $sessionLogin->userId));
        }

        if (isset($parameters['userName']) && $parameters['userName'] != "") {
            $sQuery->where(array('us.user_name like "%' . $parameters['userName'] . '%"'));
        }

        if (isset($parameters['loggedInDate']) && trim($parameters['loggedInDate']) != '') {
            $s_c_date = explode("to", $_POST['loggedInDate']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['loggedInDate'] != '') {
            $sQuery = $sQuery->where(array("u.login_attempted_datetime >='" . $start_date . "'", "u.login_attempted_datetime <='" . $end_date . "'"));
        }

        if (!empty($sWhere)) {
            $sQuery->where($sWhere);
        }

        if (!empty($sOrder)) {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        //   echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($tResult);
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {
            $attemptDateTimeArr = explode(" ", $aRow['login_attempted_datetime']);
            $attemptDateTime = $common->humanDateFormat($attemptDateTimeArr[0]) . " " . $attemptDateTimeArr[1];
            $row = array();
            $row[] = $attemptDateTime;
            $row[] = $aRow['user_name'];
            $row[] = $aRow['login_id'];
            $row[] = $aRow['ip_address'];
            $row[] = $aRow['browser'];
            $row[] = $aRow['operating_system'];
            $row[] = $aRow['login_status'];
            // $row[] = '<a href="/user/edit/' . base64_encode($aRow['user_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            $output['aaData'][] = $row;
        }

        return $output;
    }
}
