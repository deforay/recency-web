<?php

namespace Application\Model;

use Application\Service\CommonService;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class FacilitiesTable extends AbstractTableGateway
{

    protected $table = 'facilities';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchFacilitiesDetails($parameters)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('f.facility_name', 'p.province_name', 'd.district_name', 'f.email', 'f.status');
        $orderColumns = array('f.facility_name', 'p.province_name', 'd.district_name', 'f.email', 'f.status');

        /* Paging */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /* Ordering */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
                if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $roleId = $sessionLogin->roleId;

        $sQuery = $sql->select()->from(array('f' => 'facilities'))
            ->join(array('p' => 'province_details'), 'p.province_id=f.province', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id=f.district', array('district_name'), 'left');

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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        //   echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($tResult);
        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        $role = $sessionLogin->roleCode;
        $update = true;
        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = ucwords($aRow['facility_name']);
            $row[] = ucwords($aRow['province_name']);
            $row[] = ucwords($aRow['district_name']);
            $row[] = $aRow['email'];
            $row[] = ucwords($aRow['status']);
            $row[] = '<a href="/facilities/edit/' . base64_encode($aRow['facility_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function addFacilitiesDetails($params)
    {
        //\Zend\Debug\Debug::dump($params);die;
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        if (isset($params['facilityName']) && trim($params['facilityName']) != "") {
            $data = array(
                'facility_name' => $params['facilityName'],
                // 'is_vl_lab' => $params['isVlLab'],
                'province' => $params['location_one'],
                'district' => $params['location_two'],
                'city' => $params['location_three'],
                'latitude' => $params['latitude'],
                'longitude' => $params['longitude'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'status' => $params['facilityStatus'],
                'facility_type_id' => $params['facilityType'],

            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }

        if ($lastInsertedId > 0) {
            if ($params['selectedMapUser'] != '') {
                $mapArray = explode(",", $params['selectedMapUser']);
                foreach ($mapArray as $userId) {
                    $mapData = array(
                        'user_id' => $userId,
                        'facility_id' => $lastInsertedId,
                    );
                    $mapDb->insert($mapData);
                }
            }
        }
        return $lastInsertedId;
    }

    public function fetchFacilitiesDetailsById($facilityId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('f' => 'facilities'))
            ->where(array('f.facility_id' => $facilityId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        //facility map
        $umQuery = $sql->select()->from(array('um' => 'user_facility_map'))
            ->join(array('u' => 'users'), 'u.user_id=um.user_id', array('user_name'))
            ->where(array('um.facility_id' => $facilityId));
        $umQueryStr = $sql->getSqlStringForSqlObject($umQuery);
        $rResult['facilityMap'] = $dbAdapter->query($umQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateFacilitiesDetails($params)
    {
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        if (isset($params['facilityId']) && trim($params['facilityId']) != "") {
            $data = array(
                'facility_name' => $params['facilityName'],
                // 'is_vl_lab' => $params['isVlLab'],
                'province' => $params['location_one'],
                'district' => $params['location_two'],
                'city' => $params['location_three'],
                'latitude' => $params['latitude'],
                'longitude' => $params['longitude'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'status' => $params['facilityStatus'],
                'facility_type_id' => $params['facilityType'],
            );
            $updateResult = $this->update($data, array('facility_id' => base64_decode($params['facilityId'])));
            $lastId = base64_decode($params['facilityId']);
            if ($lastId > 0) {
                $mapDb->delete("facility_id=" . $lastId);
                if ($params['selectedMapUser'] != '') {
                    $mapArray = explode(",", $params['selectedMapUser']);
                    foreach ($mapArray as $userId) {
                        $mapData = array(
                            'user_id' => $userId,
                            'facility_id' => $lastId,
                        );
                        $mapDb->insert($mapData);
                    }
                }
            }
        }
        return $lastId;
    }

    public function fetchFacilitiesAllDetails()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
        $riskPopulationsDb = new \Application\Model\RiskPopulationsTable($this->adapter);

        if ($logincontainer->roleCode == 'remote_order_user') {
            $sQuery = $sql->select()->from(array('ufm' => 'user_facility_map'))
                ->join(array('f' => 'facilities'), 'f.facility_id = ufm.facility_id', array('facility_name', 'facility_id'))
                ->where(array('f.status' => 'active', 'ufm.user_id' => $logincontainer->userId));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $result['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }else {
            $sQuery = $sql->select()->from(array('f' => 'facilities'), array('facility_name'))
                ->where(array('status' => 'active'));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $result['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }
        $sQueryTest = $sql->select()->from(array('f' => 'facilities'), array('facility_name'))
            ->where(array('f.status' => 'active', 'f.facility_name IS NOT NULL', 'facility_type_id="2"'));
        $sQueryStrTest = $sql->getSqlStringForSqlObject($sQueryTest);
        $result['facilityTest'] = $dbAdapter->query($sQueryStrTest, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $result['riskPopulations'] = $riskPopulationsDb->select()->toArray();
        return $result;
    }

    public function fetchFacilitiesDetailsApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        if ($params['userId'] != '') {
            $sQuery = $sql->select()->from(array('f' => 'facilities'))
                //->join(array('r' => 'recency'), 'f.facility_id = r.facility_id', array('sample_id'))
                //->where(array('f.status' => 'active', 'r.added_by' => $params['userId']))
                ->where(array('f.status' => 'active'))
                ->order('f.facility_id DESC');
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
            $rResult['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        } else {
            $sQuery = $sql->select()->from(array('f' => 'facilities'))
                ->where(array('status' => 'active'));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
            $rResult['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }
        $sQueryTest = $sql->select()->from(array('f' => 'facilities'), array('facility_name'))
            ->where(array('f.status' => 'active', 'f.facility_name IS NOT NULL', 'facility_type_id="2"'));
        $sQueryStrTest = $sql->getSqlStringForSqlObject($sQueryTest);
        $rResult['facilityTest'] = $dbAdapter->query($sQueryStrTest, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $sQueryTestFType = $sql->select()->from(array('f' => 'testing_facility_type'), array('testing_facility_type_name'))
            ->where(array('f.testing_facility_type_status' => 'active'));
        $sQueryStrTestFType = $sql->getSqlStringForSqlObject($sQueryTestFType);
        $rResult['testingFacilityType'] = $dbAdapter->query($sQueryStrTestFType, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $rResult;
    }
    public function fetchFacilityByLocation($params)
    {
        $sessionLogin = new Container('credo');
        $roleCode = $sessionLogin->roleCode;
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('f' => 'facilities'))->columns(array('facility_id', 'facility_name'));
        if ($roleCode != 'admin') {
            $sQuery = $sQuery->join(array('ufm' => 'user_facility_map'), 'f.facility_id=ufm.facility_id', array());
            $sQuery = $sQuery->join(array('u' => 'users'), 'ufm.user_id=u.user_id', array());
            $sQuery = $sQuery->where(array('u.user_id' => $sessionLogin->userId));
        }
        if ($params['locationOne'] != '') {
            $sQuery = $sQuery->where(array('province' => $params['locationOne']));
            if ($params['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('district' => $params['locationTwo']));
            }
            if ($params['locationThree'] != '') {
                $sQuery = $sQuery->where(array('city' => $params['locationThree']));
            }
        }
        if (isset($params['facilityId']) && $params['facilityId'] != null) {
            $fDeocde = json_decode($params['facilityId']);
            if (!empty($fDeocde)) {
                $sQuery = $sQuery->where('facility_id NOT IN(' . implode(",", $fDeocde) . ')');
            }
        }
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $fResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $fResult;
    }

    public function checkFacilityName($fName, $facilityType)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('facilities')->columns(array('facility_id', 'facility_name', 'facility_type_id'))
            ->where(array('facility_name' => trim($fName)));
        // if ($facilityType == 1) {
        //     $fQuery = $fQuery->where('(facility_type_id IS NULL OR facility_type_id="" OR facility_type_id="1"  OR facility_type_id="0")');
        // } else if ($facilityType == 2) {
        //     $fQuery = $fQuery->where(array('facility_type_id' => $facilityType));
        // }
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery); // Get the string of the Sql, instead of the Select-instance
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $fResult;
    }

    public function checkDistrictName($districtName, $provinceId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $dQuery = $sql->select()->from('district_details')
            ->where(array('district_name' => trim($districtName), 'province_id' => $provinceId));
        $dQueryStr = $sql->getSqlStringForSqlObject($dQuery); // Get the string of the Sql, instead of the Select-instance
        $dResult = $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $dResult;
    }

    public function checkCityName($cityName, $districtId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $cQuery = $sql->select()->from('city_details')
            ->where(array('city_name' => trim($cityName), 'district_id' => $districtId));
        $cQueryStr = $sql->getSqlStringForSqlObject($cQuery); // Get the string of the Sql, instead of the Select-instance
        $cResult = $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $cResult;
    }

    public function fetchLocationBasedFacility($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fResult = '';
        if (isset($params['facilityId']) && $params['facilityId'] != null) {
            $sQuery = $sql->select()->from(array('f' => 'facilities'))->columns(array('facility_id', 'facility_name', 'province', 'district', 'city'))
                ->join(array('p' => 'province_details'), 'p.province_id=f.province', array('province_name'), 'left')
                ->join(array('d' => 'district_details'), 'd.district_id=f.district', array('district_name'), 'left')
                ->join(array('c' => 'city_details'), 'c.city_id=f.city', array('city_name'), 'left');
            $fDeocde = base64_decode($params['facilityId']);
            $sQuery = $sQuery->where(array('facility_id' => $fDeocde));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
            $fResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        }
        return json_encode($fResult);
    }
}
