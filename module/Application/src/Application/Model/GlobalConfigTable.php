<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

class GlobalConfigTable extends AbstractTableGateway {

    protected $table = 'global_config';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchGlobalConfigDetails($parameters) {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleId;
        $roleCode = $sessionLogin->roleCode;
        $common = new CommonService();
        $aColumns = array('display_name','global_value');
        $orderColumns = array('display_name','global_value');

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
                        $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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
            $roleId=$sessionLogin->roleId;

            $sQuery = $sql->select()->from('global_config');

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
                "aaData" => array()
            );

            $role = $sessionLogin->roleCode;
            $update = true;
            foreach ($rResult as $aRow) {
            $row = array();
            $row[] = ucwords($aRow['display_name']);
            $row[] = ucwords($aRow['global_value']);

            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchGlobalConfigAllDetails()
    {
        return $this->select()->toArray();
    }

    public function fetchGlobalConfigAllDetailsApi()
    {
        $response['status'] = 'success' ;
        $response['config'] = $this->select()->toArray();
        return $response;
    }

    public function updateGlobalConfigDetails($params)
    {
        $n = count($params['gobalConfigId']);
        $result = 0;
        $i = 0;
        for($i=0;$i<$n;$i++){
            if(isset($params['configValue'][$i]) && trim($params['configValue'][$i])!="")
            {
                $data = array(
                    'global_value' => $params['configValue'][$i]
                );
                $updateResult = $this->update($data,array('config_id'=>base64_decode($params['gobalConfigId'][$i])));
                if($updateResult > 0){
                    $result = 1;
                }
            }
            $lastConfigId = base64_decode($params['gobalConfigId'][$i]);
        }
        $selectedRecencyUser['global_value'] = $params['selectedRecencyUser'];
        $MantatoryUpdateResult = $this->update($selectedRecencyUser,array('config_id'=>$lastConfigId));
        if($MantatoryUpdateResult > 0){
            $result = 1;
        }
        return $result;
    }

    public function fetchAllGlobalConfigListApi($params)
    {
        $common = new CommonService();
        $config = new \Zend\Config\Reader\Ini();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $rResult = $this->select()->toArray();
        foreach($rResult as $result){
            if(isset($result['config_id']) && $result['config_id']!='') {
                $response['status']='success';
                $response[] = array(
                    $result['display_name'] => $result['global_value']
                );
            } else {
                $response["status"] = "fail";
                $response["message"] = "Date not found!";
            }
        }
       return $response;
    }

    public function fetchRecencyMandatoryDetailsApi()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $arr = array();
        $resultArr = array();
        $rResult = $this->select()->toArray();
        for ($i = 0; $i < sizeof($rResult); $i++) {
            $arr[$rResult[$i]['global_name']] = $rResult[$i]['global_value'];
        }
        if(isset($arr['mandatory_fields']) && trim($arr['mandatory_fields'])!= ''){
            $explodField = explode(",",$arr['mandatory_fields']);
            for($f=0;$f<count($explodField); $f++){
                $resultArr[] = str_replace(' ', '_', strtolower($explodField[$f]));
            }
        }
        // \Zend\Debug\Debug::dump($explodField);die;
        $row[] = in_array("Sample Id",$explodField)?"sampleId":"";
        $row[] = in_array("Patient Id",$explodField)?"patientId":"";
        $row[] = in_array("Facility Name",$explodField)?"facilityId":"";
        $row[] = in_array("Hiv Diagnosis Date",$explodField)?"hivDiagnosisDate":"";
        $row[] = in_array("Hiv Recency Date",$explodField)?"hivRecencyDate":"";
        $row[] = in_array("Control Line",$explodField)?"ctrlLine":"";
        $row[] = in_array("Positive Verification Line",$explodField)?"positiveLine":"";
        $row[] = in_array("Long Term Verification Line",$explodField)?"longTermLine":"";
        $row[] = in_array("Dob",$explodField)?"dob":"";
        $row[] = in_array("Age",$explodField)?"age":"";
        $row[] = in_array("Gender",$explodField)?"gender":"";
        $row[] = in_array("Province",$explodField)?"location_one":"";
        $row[] = in_array("District",$explodField)?"location_two":"";
        $row[] = in_array("City",$explodField)?"location_three":"";
        $row[] = in_array("Marital Status",$explodField)?"maritalStatus":"";
        $row[] = in_array("Residence",$explodField)?"residence":"";
        $row[] = in_array("Education Level",$explodField)?"educationLevel":"";
        $row[] = in_array("Risk Population",$explodField)?"riskPopulation":"";
        // $row[] = in_array("Other Risk Population",$explodField)?"otherriskPopulation":"";
        $row[] = in_array("Pregnancy Status",$explodField)?"pregnancyStatus":"";
        $row[] = in_array("Current Sexual Partner",$explodField)?"currentSexualPartner":"";
        $row[] = in_array("Past Hiv Testing",$explodField)?"pastHivTesting":"";

        $row[] = in_array("Last HIV Status",$explodField)?"lastHivStatus":"";
        $row[] = in_array("Patient on ART",$explodField)?"patientOnArt":"";

        $row[] = in_array("Test Last 12 Month",$explodField)?"testLast12Month":"";
        $row[] = in_array("Latitude",$explodField)?"latitude":"";
        $row[] = in_array("Longitude",$explodField)?"longitude":"";

        $output = array_filter($row);
        if(isset($resultArr) && $resultArr !='') {
            $response['status']='success';
            $response['fields'] = array_values($output);
        } else {
            $response["status"] = "failed";
            $response["message"] = "Date not found!";
        }
       return $response;
    }
}
?>
