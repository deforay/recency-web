<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class DistrictTable extends AbstractTableGateway {

    protected $table = 'district_details';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    public function fetchAllDistrictListApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        if(isset($params['provinceId']) && $params['provinceId']!=''){
                $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'))
                ->where(array('province_id' => $params['provinceId'] ));
        }
        else
        {
                $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'));
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if($rResult) {
                $response['status']='success';
                $response['district'] = $rResult;
        }

        else {
                $response["status"] = "fail";
                $response["message"] = "Please select valid District detail!";
        }
        return $response;
    }
    public function fetchDistrictDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        
        $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'))
                            ->where(array('province_id' => $params['selectValue'] ));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        //fetch facility data
        $fQuery = $sql->select()->from(array('f' => 'facilities'))
                            ->where(array('province' => $params['selectValue']))
                            ->where('(facility_type_id IS NULL OR facility_type_id="" OR facility_type_id="1"  OR facility_type_id="0")');
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return array('district'=>$rResult,'facility'=>$fResult);

    }

    public function fetchAllDistrictDetails($parameters)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('province_name','district_name');
        $orderColumns = array('province_name','district_name');

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

        $sQuery = $sql->select()->from(array('d' => 'district_details'))
        ->join(array('p' => 'province_details'), 'p.province_id=d.province_id', array('province_name'), 'left')
        ;

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
            $row[] = ucwords($aRow['province_name']);
            $row[] = ucwords($aRow['district_name']);
            $row[] = '<a href="/district/edit/' . base64_encode($aRow['district_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            $output['aaData'][] = $row;
        }

        return $output;
    }
      
    public function addDistrictDetails($params)
    {
        if (isset($params['districtName']) && trim($params['districtName']) != "") {
            $data = array(
                'district_name' => $params['districtName'],
                'province_id' => $params['provinceName'],
            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    
    public function fetchDistrictDetailsById($districtId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('d' => 'district_details'))
            ->where(array('d.district_id' => $districtId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    
    public function updateDistrictDetails($params)
    {
        if (isset($params['districtId']) && trim($params['districtId']) != "") {
            $data = array(
                'district_name' => $params['districtName'],
                'province_id' => $params['provinceName'],
            );
            $updateResult = $this->update($data, array('district_id' => $params['districtId']));
           
        }
        return $params['districtId'];
    }

}
?>