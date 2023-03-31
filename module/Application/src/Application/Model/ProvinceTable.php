<?php
namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;

class ProvinceTable extends AbstractTableGateway {

     protected $table = 'province_details';

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }
        public function fetchAllProvienceListApi()
        {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('pd' => 'province_details'))->columns(array('province_id','province_name'));

            $sQueryStr = $sql->buildSqlString($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if($rResult) {
                $response['status']='success';
                $response['province'] = $rResult;
            }

            else {
                $response["status"] = "fail";
                $response["message"] = "Please select valid Provience details!";
            }
            return $response;
        }
        public function fetchProvinceDetails($params)
        {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('pd' => 'province_details'))->columns(array('province_id','province_name'));

            $sQueryStr = $sql->buildSqlString($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            return $rResult;
        }

        
        public function fetchAllProvinceDetails($parameters,$acl)
        {
    
            /* Array of database columns which should be read and sent back to DataTables. Use a space where
             * you want to insert a non-database field (for example a counter or static image)
             */
            $sessionLogin = new Container('credo');
            $common = new CommonService();
            $aColumns = array('province_name');
            $orderColumns = array('province_name');
    
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
    
            $sQuery = $sql->select()->from(array('p' => 'province_details'))
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
            if ($acl->isAllowed($roleCode, 'Application\Controller\ProvinceController', 'edit')) {
                $update = true;
            } else {
                $update = false;
            }
            foreach ($rResult as $aRow) {
    
                $row = array();
                $row[] = ucwords($aRow['province_name']);
                if($update){
                    $row[] = '<a href="/province/edit/' . base64_encode($aRow['province_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
                }
                $output['aaData'][] = $row;
            }
    
            return $output;
        }

        
        public function addProvinceDetails($params)
    {
        //\Zend\Debug\Debug::dump($params);die;
        if (isset($params['provinceName']) && trim($params['provinceName']) != "") {
            $data = array(
                'province_name' => $params['provinceName'],
            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    
    public function fetchProvinceDetailsById($provinceId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('p' => 'province_details'))
            ->where(array('p.province_id' => $provinceId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }
    

    public function updateProvinceDetails($params)
    {
        if (isset($params['provinceId']) && trim($params['provinceId']) != "") {
            $data = array(
                'province_name' => $params['provinceName'],
            );
            $updateResult = $this->update($data, array('province_id' => $params['provinceId']));
           
        }
        return $params['provinceId'];
    }

    public function fetchProvince()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('pd' => 'province_details'))->columns(array('province_id','province_name'));

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

     }

?>
