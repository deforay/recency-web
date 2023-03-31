<?php
namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;

class CityTable extends AbstractTableGateway {

     protected $table = 'city_details';

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }

          public function fetchAllCityListApi($params)
          {
               $common = new CommonService();
               $config = new \Laminas\Config\Reader\Ini();
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);


               if(isset($params['districtId']) && $params['districtId']!=''){
                    $sQuery = $sql->select()->from(array('cd' => 'city_details'))->columns(array('city_id','district_id','city_name'))
                    ->where(array('district_id' => $params['districtId'] ));
               }
               else
               {
                    $sQuery = $sql->select()->from(array('cd' => 'city_details'))->columns(array('city_id','district_id','city_name'));
               }


               $sQueryStr = $sql->buildSqlString($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               if($rResult) {
                    $response['status']='success';
                    $response['city'] = $rResult;
               }

               else {
                    $response["status"] = "fail";
                    $response["message"] = "Please select valid City detail!";
               }
               return $response;
          }
          public function fetchCityDetails($params)
          {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);
                $sQuery = $sql->select()->from(array('cd' => 'city_details'))->columns(array('city_id','district_id','city_name'))
                                    ->where(array('district_id' => $params['selectValue']));
               $sQueryStr = $sql->buildSqlString($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               //fetch facility data
                $fQuery = $sql->select()->from(array('f' => 'facilities'))
                            ->where(array('district' => $params['selectValue']))
                            ->where('(facility_type_id IS NULL OR facility_type_id="" OR facility_type_id="1"  OR facility_type_id="0")');
                $fQueryStr = $sql->buildSqlString($fQuery);
                $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

                return array('city'=>$rResult,'facility'=>$fResult);
          }

          public function fetchFacilityDetails($params)
          {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
                //fetch facility data
                $fQuery = $sql->select()->from(array('f' => 'facilities'))
                            ->where(array('city' => $params['selectValue']))
                            ->where('(facility_type_id IS NULL OR facility_type_id="" OR facility_type_id="1"  OR facility_type_id="0")');
                $fQueryStr = $sql->buildSqlString($fQuery);
                $fResult['facility'] = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                return $fResult;
          }

          public function fetchAllCityDetails($parameters,$acl)
          {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('district_name','city_name');
        $orderColumns = array('district_name','city_name');

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

        $sQuery = $sql->select()->from(array('c' => 'city_details'))
        ->join(array('d' => 'district_details'), 'd.district_id=c.district_id', array('district_name'), 'left')
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
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        $roleCode = $sessionLogin->roleCode;
		if ($acl->isAllowed($roleCode, 'Application\Controller\CityController', 'edit')) {
            $update = true;
        } else {
            $update = false;
        }
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['district_name']);
            $row[] = ucwords($aRow['city_name']);
            if($update){
                $row[] = '<a href="/city/edit/' . base64_encode($aRow['city_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            }
            $output['aaData'][] = $row;
        }

        return $output;
    }

    
    public function addCityDetails($params)
    {
        if (isset($params['cityName']) && trim($params['cityName']) != "") {
            $data = array(
                'city_name' => $params['cityName'],
                'district_id' => $params['districtName'],
            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    
    public function fetchCityDetailsById($cityId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('c' => 'city_details'))
                                ->where(array('c.city_id' => $cityId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    
    public function updateCityDetails($params)
    {
        if (isset($params['cityId']) && trim($params['cityId']) != "") {
          $data = array(
               'city_name' => $params['cityName'],
               'district_id' => $params['districtName'],
           );
            $updateResult = $this->update($data, array('city_id' => $params['cityId']));
           
        }
        return $params['cityId'];
    }
     }
?>