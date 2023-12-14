<?php

namespace Application\Model;

use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Session\Container;

class SettingsTable extends AbstractTableGateway
{

    protected $table = 'test_kit_information';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchSettingsDetails($parameters, $acl)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('reference_result', 'kit_lot_no', 'DATE_FORMAT(kit_expiry_date,"%d-%b-%Y")', 'status');
        $orderColumns = array('reference_result', 'kit_lot_no', 'kit_expiry_date', 'status');

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
        $roleId = $sessionLogin->roleId;

        $sQuery = $sql->select()->from(array('t' => 'test_kit_information'))
            ->join(array('u' => 'users'), 't.added_by = u.user_id', array('user_name'))
            //->join(array('p' => 'province_details'), 'p.province_id=f.province', array('province_name'), 'left')
            //->join(array('d' => 'district_details'), 'd.district_id=f.district', array('district_name'), 'left')
        ;

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
            "aaData" => array(),
        );

        $roleCode = $sessionLogin->roleCode;
        $update = (bool) $acl->isAllowed($roleCode, 'Application\Controller\SettingsController', 'edit');
        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = str_replace("_", " ", ucwords($aRow['reference_result']));
            $row[] = ucwords($aRow['kit_lot_no']);
            $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
            $row[] = ucwords($aRow['status']);
            $row[] = date('d-M-Y H:s A', strtotime($aRow['added_on']));
            $row[] = ucwords($aRow['user_name']);
            if ($update) {
                $row[] = '<a href="/settings/edit/' . base64_encode($aRow['test_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            }
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function addSettingsDetails($params)
    {

        $logincontainer = new Container('credo');
        $common = new CommonService();
        if (isset($params['testKitName']) && trim($params['testKitName']) != "") {
            $data = array(
                'reference_result' => $params['testKitName'],
                'kit_lot_no' => $params['testKitNumber'],
                'kit_expiry_date' => $common->dbDateFormat($params['testKitDate']),
                'status' => $params['status'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId,
            );

            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchSettingsDetailsById($testId)
    {

        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $common = new CommonService();
        $sQuery = $sql->select()->from(array('t' => 'test_kit_information'))
            ->where(array('t.test_id' => $testId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        //facility map
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function updateSettingsDetails($params)
    {
        $logincontainer = new Container('credo');
        $mapDb = new UserFacilityMapTable($this->adapter);
        $common = new CommonService();
        if (isset($params['testKitName']) && trim($params['testKitName']) != "") {
            $data = array(
                'reference_result' => $params['testKitName'],
                'kit_lot_no' => $params['testKitNumber'],
                'kit_expiry_date' => $common->dbDateFormat($params['testKitDate']),
                'status' => $params['status'],
            );
            $updateResult = $this->update($data, array('test_id' => $params['testId']));
        }
        return  $params['testId'];
    }

    public function fetchKitLotDetails()
    {
        return $this->select(array('status' => 'active'))->toArray();
    }
}
