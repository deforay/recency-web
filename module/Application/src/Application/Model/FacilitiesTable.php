<?php

namespace Application\Model;

use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Session\Container;

class FacilitiesTable extends AbstractTableGateway
{

    protected $table = 'facilities';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchFacilitiesDetails($parameters, $acl)
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

        $sQuery = $sql->select()->from(array('f' => 'facilities'))
            ->join(array('p' => 'province_details'), 'p.province_id=f.province', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id=f.district', array('district_name'), 'left');

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
        $update = (bool) $acl->isAllowed($roleCode, 'Application\Controller\FacilitiesController', 'edit');
        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = ucwords($aRow['facility_name']);
            $row[] = ucwords($aRow['province_name']);
            $row[] = $aRow['district_name'];
            $row[] = $aRow['email'];
            $row[] = ucwords($aRow['status']);
            if ($update) {
                $row[] = '<a href="/facilities/edit/' . base64_encode($aRow['facility_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            }
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function addFacilitiesDetails($params)
    {
        //\Zend\Debug\Debug::dump($params);die;
        $mapDb = new UserFacilityMapTable($this->adapter);
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

        if ($lastInsertedId > 0 && $params['selectedMapUser'] != '') {
            $mapArray = explode(",", $params['selectedMapUser']);
            foreach ($mapArray as $userId) {
                $mapData = array(
                    'user_id' => $userId,
                    'facility_id' => $lastInsertedId,
                );
                $mapDb->insert($mapData);
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
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        //facility map
        $umQuery = $sql->select()->from(array('um' => 'user_facility_map'))
            ->join(array('u' => 'users'), 'u.user_id=um.user_id', array('user_name'))
            ->where(array('um.facility_id' => $facilityId));
        $umQueryStr = $sql->buildSqlString($umQuery);
        $rResult['facilityMap'] = $dbAdapter->query($umQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateFacilitiesDetails($params)
    {
        $mapDb = new UserFacilityMapTable($this->adapter);
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

    public function fetchTestingHubs()
    {
        return $this->select(array('facility_type_id' => 2))->toArray();
    }

    public function fetchFacilitiesAllDetails()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');

        $sQuery = $sql->select()
            ->from(array('f' => 'facilities'))
            ->columns([
                'facility_id',
                'facility_name',
                'facility_type_id'
            ]);

        if (!empty($logincontainer->facilityMap)) {
            $sQuery = $sQuery->where('f.facility_id IN (' . $logincontainer->facilityMap . ')');
        } else {
            $sQuery = $sQuery->where(['status' => 'active']);
        }
        $sQuery = $sQuery->where(array('facility_type_id != 2'));
        $sQuery = $sQuery->order('facility_name ASC');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $fetchResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        // Populating Collection/Client Sites
        foreach ($fetchResult as $key => $row) {
            $result['facility'][$key]['facility_id'] = $row['facility_id'];
            $result['facility'][$key]['facility_name'] = $row['facility_name'];
        }

        // Populating Testing Sites
        $sQuery = $sql->select()->from(array('f' => 'facilities'))->columns(array('facility_id', 'facility_name', 'facility_type_id'));
        $sQuery = $sQuery->where(array('f.status' => 'active', 'f.facility_type_id = 2'));
        $sQuery = $sQuery->order('facility_name ASC');
        $sQueryStr = $sql->buildSqlString($sQuery);
        $fetchResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        foreach ($fetchResult as $key => $row) {
            $result['facilityTest'][$key]['facility_id'] = $row['facility_id'];
            $result['facilityTest'][$key]['facility_name'] = $row['facility_name'];
        }
        $riskPopulationsDb = new RiskPopulationsTable($this->adapter);
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
            $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
            $rResult['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        } else {
            $sQuery = $sql->select()->from(array('f' => 'facilities'))
                ->where(array('status' => 'active'));
            $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
            $rResult['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }
        $sQueryTest = $sql->select()->from(array('f' => 'facilities'))
            ->where(array('f.status' => 'active', 'f.facility_name IS NOT NULL', 'facility_type_id="2"'));
        $sQueryStrTest = $sql->buildSqlString($sQueryTest);
        $rResult['facilityTest'] = $dbAdapter->query($sQueryStrTest, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $sQueryTestFType = $sql->select()->from(array('f' => 'testing_facility_type'))
            ->where(array('f.testing_facility_type_status' => 'active'));
        $sQueryStrTestFType = $sql->buildSqlString($sQueryTestFType);
        $rResult['testingFacilityType'] = $dbAdapter->query($sQueryStrTestFType, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $rResult;
    }
    public function fetchFacilityByLocation($params)
    {
        $common = new CommonService();
        $sessionLogin = new Container('credo');
        $roleCode = $sessionLogin->roleCode;
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('f' => 'facilities'))
            ->columns(array('facility_id', 'facility_name', 'facility_type_id'));
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
            if ($params['locationThree'] != '' && $params['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('city' => $params['locationThree']));
            }
        }
        if (isset($params['facilityId']) && $params['facilityId'] != null) {
            $fDeocde = json_decode($params['facilityId']);
            if (!empty($fDeocde)) {
                $sQuery = $sQuery->where('facility_id NOT IN(' . implode(",", $fDeocde) . ')');
            }
        }
        if (isset($params['hivRecencyTest']) && trim($params['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($params['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        //$sQuery = $sQuery->where(array('facility_type_id != 2'));
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
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
        $fQueryStr = $sql->buildSqlString($fQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function checkDistrictName($districtName, $provinceId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $dQuery = $sql->select()->from('district_details')
            ->where(array('district_name' => trim($districtName), 'province_id' => $provinceId));
        $dQueryStr = $sql->buildSqlString($dQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($dQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function checkCityName($cityName, $districtId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $cQuery = $sql->select()->from('city_details')
            ->where(array('city_name' => trim($cityName), 'district_id' => $districtId));
        $cQueryStr = $sql->buildSqlString($cQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($cQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
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
            $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
            $fResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        }
        return json_encode($fResult);
    }

    public function fetchFacilitiesByFacilityId($facilityId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('f' => 'facilities'))
            ->columns(['facility_id', 'facility_name', 'facility_type_id'])
            ->where(array('f.facility_id' => $facilityId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $fetchResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        // Populating Collection/Client Sites
        foreach ($fetchResult as $key => $row) {
            $result['facility'][$key]['facility_id'] = $row['facility_id'];
            $result['facility'][$key]['facility_name'] = $row['facility_name'];
        }
        return $result;
    }
}
