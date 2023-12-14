<?php
namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

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
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                        $sOrder .= $aColumns[(int) $parameters['iSortCol_' . $i]] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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

            $role = $sessionLogin->roleCode;
            $update = true;
            foreach ($rResult as $aRow) {
                $row = array();
                $row[] = ($aRow['display_name']);
                if($aRow['global_name'] == 'logo_image'){
                    $row[] = '<img src="/uploads/logo_image/'. $aRow['global_value'].'" style="width:100px;height:100px;"/><br>';
                }
                $row[] = ($aRow['global_value']);

                $output['aaData'][] = $row;
            }
        return $output;
    }

    public function fetchGlobalConfigAllDetails()
    {
        return $this->select()->toArray();
    }

    public function fetchGlobalConfig()
    {
        $res =  $this->select()->toArray();
        $response = array();
        foreach($res as $row){
            $response[$row['global_name']] = $row['global_value'];
        }

        return $response;
    }    

    public function fetchGlobalConfigAllDetailsApi()
    {
        $response['status'] = 'success' ;
        $response['config'] = $this->select()->toArray();
        return $response;
    }

    public function updateGlobalConfigDetails($params)
    {
        $result = 1;
        foreach ($params as $fieldName => $fieldValue) {
            $this->update(array('global_value' => $fieldValue), array('global_name' => $fieldName));
        }

        if (isset($params['removeLogoImage']) && $params['removeLogoImage'] != '' && file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image" . DIRECTORY_SEPARATOR . $params['removeLogoImage'])) {
            unlink(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image" . DIRECTORY_SEPARATOR . $params['removeLogoImage']);
            $this->update(array('global_value' => ""), array('global_name' => 'logo_image'));
        }
        if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image")) {
            mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image");
        }
        
        $pathname = UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo_image";
        if (move_uploaded_file($_FILES["logo_image"]["tmp_name"], $pathname . DIRECTORY_SEPARATOR .$_FILES['logo_image']['name'])) {
            $this->update(array('global_value' => $_FILES['logo_image']['name']), array('global_name' => 'logo_image'));
        }

        $selectedDataIndex = explode(",",$params['selectedRecencyDataAttr']);
        sort($selectedDataIndex);
        $decodeAllFields  = json_decode($params['allFields']);
        foreach($selectedDataIndex as $index){
            $selectedValue[] = $decodeAllFields[$index];
        }
        $MantatoryUpdateResult = $this->update(array('global_value' => implode(",",$selectedValue) ), array('global_name' => 'mandatory_fields'));

        $selectedDataIndex2 = explode(",",$params['selectedRecencyDataAttr2']);
        sort($selectedDataIndex2);
        $decodeAllFields2  = json_decode($params['allFields2']);
        foreach($selectedDataIndex2 as $index){
            $selectedValue2[] = $decodeAllFields2[$index];
        }
        $MantatoryUpdateResult2 = $this->update(array('global_value' => implode(",",$selectedValue2) ), array('global_name' => 'display_fields'));
        
        if($MantatoryUpdateResult > 0 || $MantatoryUpdateResult2 > 0){
            $result = 1;
        }
        return $result;
    }

    public function fetchAllGlobalConfigListApi($params)
    {
        $common = new CommonService();
        $config = new \Laminas\Config\Reader\Ini();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $rResult = $this->select()->toArray();
        foreach($rResult as $result){
            if(isset($result['config_id']) && $result['config_id'] != '') {
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
        $rResult = $this->select()->toArray();
        $counter = count($rResult);
        for ($i = 0; $i < $counter; $i++) {
            $arr[$rResult[$i]['global_name']] = $rResult[$i]['global_value'];
        }
        if(isset($arr['mandatory_fields']) && trim($arr['mandatory_fields']) != ''){
            $explodField = explode(",",$arr['mandatory_fields']);
        }
        $row[] = in_array("Sample Id",$explodField)?"sampleId":"";
        $row[] = in_array("Patient Id",$explodField)?"patientId":"";
        $row[] = in_array("Sample Collection Date from the Client",$explodField)?"sampleCollectionDate":"";
        $row[] = in_array("Sample Receipt Date at the Recency Testing Site",$explodField)?"sampleReceiptDate":"";
        $row[] = in_array("Received Specimen Type",$explodField)?"receivedSpecimenType":"";
        $row[] = in_array("Facility Name",$explodField)?"facilityId":"";
        $row[] = in_array("Province",$explodField)?"location_one":"";
        $row[] = in_array("District",$explodField)?"location_two":"";
        $row[] = in_array("City",$explodField)?"location_three":"";
        $row[] = in_array("Hiv Diagnosis Date",$explodField)?"hivDiagnosisDate":"";
        $row[] = in_array("Past Hiv Testing",$explodField)?"pastHivTesting":"";
        $row[] = in_array("Test Last 12 Month",$explodField)?"testLast12Month":"";
        $row[] = in_array("Last HIV Status",$explodField)?"lastHivStatus":"";
        $row[] = in_array("Patient on ART",$explodField)?"patientOnArt":"";

        $row[] = in_array("Test Kit Lot No",$explodField)?"testKitLotNo":"";
        $row[] = in_array("Kit Expiry Date",$explodField)?"testKitExpDate":"";
        $row[] = in_array("Tester Name",$explodField)?"testerName":"";
        $row[] = in_array("Testing Modality",$explodField)?"testingModality":"";
        $row[] = in_array("Testing Facility",$explodField)?"testingFacility":"";



        $row[] = in_array("Hiv Recency Test Date",$explodField)?"hivRecencyTestDate":"";
        $row[] = in_array("Control Line",$explodField)?"ctrlLine":"";
        $row[] = in_array("Verification Line",$explodField)?"positiveLine":"";
        $row[] = in_array("Long Term Line",$explodField)?"longTermLine":"";
              
       
        $row[] = in_array("Viral Load Test Date",$explodField)?"vlTestDate":"";
        $row[] = in_array("Viral Load Result",$explodField)?"vlLoadResult":"";

        $row[] = in_array("Dob",$explodField)?"dob":"";
        $row[] = in_array("Age",$explodField)?"age":"";
        $row[] = in_array("Gender",$explodField)?"gender":"";
        $row[] = in_array("Pregnancy Status",$explodField)?"pregnancyStatus":"";
        $row[] = in_array("Marital Status",$explodField)?"maritalStatus":"";
        $row[] = in_array("Education Level",$explodField)?"educationLevel":"";
        $row[] = in_array("Risk Population",$explodField)?"riskPopulation":"";
        $row[] = in_array("Residence",$explodField)?"residence":"";
        $row[] = in_array("Current Sexual Partner",$explodField)?"currentSexualPartner":"";
        $row[] = in_array("Experience Violence Last 12 Month",$explodField)?"violenceLast12Month":"";
        $row[] = in_array("Comments",$explodField)?"notes":"";

        $output = array_filter($row);
        if(isset($explodField) && $explodField != '') {
            $response['status']='success';
            $response['fields'] = array_values($output);
        } else {
            $response["status"] = "failed";
            $response["message"] = "Data not found!";
        }
       return $response;
    }
    
    public function fetchRecencyHideDetailsApi()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $arr = array();
        $rResult = $this->select()->toArray();
        $counter = count($rResult);
        for ($i = 0; $i < $counter; $i++) {
            $arr[$rResult[$i]['global_name']] = $rResult[$i]['global_value'];
        }
        $explodField = array();
        if(isset($arr['display_fields']) && trim($arr['display_fields']) != ''){
            $explodField = explode(",",$arr['display_fields']);
        }
        $row['sampleId'] = in_array("Sample Id",$explodField);
        $row['patientId'] = in_array("Patient Id",$explodField);
        $row['sampleCollectionDate'] = in_array("Sample Collection Date from the Client",$explodField);
        $row['sampleReceiptDate'] = in_array("Sample Receipt Date at the Recency Testing Site",$explodField);
        $row['receivedSpecimenType'] = in_array("Received Specimen Type",$explodField);
        $row['facilityId'] = in_array("Facility Name",$explodField);
        $row['location_one'] = in_array("Province",$explodField);
        $row['location_two'] = in_array("District",$explodField);
        $row['location_three'] = in_array("City",$explodField);
        $row['hivDiagnosisDate'] = in_array("Hiv Diagnosis Date",$explodField);
        $row['pastHivTesting'] = in_array("Past Hiv Testing",$explodField);
        $row['testLast12Month'] = in_array("Test Last 12 Month",$explodField);
        $row['lastHivStatus'] = in_array("Last HIV Status",$explodField);
        $row['patientOnArt'] = in_array("Patient on ART",$explodField);
       
        $row['hivRecencyTestDate'] = in_array("Hiv Recency Test Date",$explodField);
        $row['ctrlLine'] = in_array("Control Line",$explodField);
        $row['positiveLine'] = in_array("Verification Line",$explodField);
        $row['longTermLine'] = in_array("Long Term Line",$explodField);
        $row['testKitLotNo'] = in_array("Test Kit Lot No",$explodField);
        $row['testKitExpDate'] = in_array("Kit Expiry Date",$explodField);
        $row['testerName'] = in_array("Tester Name",$explodField);
        $row['testingModality'] = in_array("Testing Modality",$explodField);
        $row['testingFacility'] = in_array("Testing Facility",$explodField);
        $row['vlTestDate'] = in_array("Viral Load Test Date",$explodField);
        $row['vlLoadResult'] = in_array("Viral Load Result",$explodField);
        $row['dob'] = in_array("Dob",$explodField);
        $row['age'] = in_array("Age",$explodField);
        $row['gender'] = in_array("Gender",$explodField);
        $row['pregnancyStatus'] = in_array("Pregnancy Status",$explodField);
        $row['maritalStatus'] = in_array("Marital Status",$explodField);
        $row['educationLevel'] = in_array("Education Level",$explodField);
        $row['riskPopulation'] = in_array("Risk Population",$explodField);
        $row['residence'] = in_array("Residence",$explodField);
        $row['currentSexualPartner'] = in_array("Current Sexual Partner",$explodField);
        $row['violenceLast12Month'] = in_array("Experience Violence Last 12 Month",$explodField);
        $row['notes'] = in_array("Comments",$explodField);

       
        
        // $output = array_filter($row);
        // $response['fields'] = array_values($row);
        if(isset($explodField)) {
            $response['status']='success';
            $response['fields'] = array($row);
        } else {
            $response["status"] = "failed";
            $response["message"] = "Data not found!";
        }
       return $response;
    }

    public function getGlobalValue($globalName) {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('global_config')->where(array('global_name' => $globalName));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $configValues = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $configValues[0]['global_value'];
        
    }

    
    public function fetchTechnicalSupportDetailsApi()
    {
        $common = new CommonService();
        $config = new \Laminas\Config\Reader\Ini();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('global_config')
                        ->where('global_name IN ("technical_support_name","admin_phone","admin_email")')
                        ->order("config_id DESC");
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if(count($rResult) > 0) {
                $response['status']='success';
                $response['result'] =$rResult;
            } else {
                $response["status"] = "fail";
                $response["message"] = "Date not found!";
            }
       return $response;
    }

    public function fetchAllGlobalConfigDataForMenu(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery =  $sql->select()->from('global_config');
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}
?>
