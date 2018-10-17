<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;
use \Application\Model\FacilitiesTable;

class RecencyTable extends AbstractTableGateway {

    protected $table = 'recency';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchRecencyDetails($parameters) {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');
        $role = $sessionLogin->roleId;
        $roleCode = $sessionLogin->roleCode;
        $common = new CommonService();
        $aColumns = array('r.sample_id','r.patient_id','f.facility_name','DATE_FORMAT(r.hiv_diagnosis_date,"%d-%b-%Y")','DATE_FORMAT(r.hiv_recency_date,"%d-%b-%Y")','r.control_line','r.positive_verification_line','r.long_term_verification_line');
        $orderColumns = array('r.sample_id','r.patient_id','f.facility_name','r.hiv_diagnosis_date','r.hiv_recency_date','r.control_line','r.positive_verification_line','r.long_term_verification_line');

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

          $sQuery = $sql->select()->from(array( 'r' => 'recency' ))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
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
          if($roleCode=='user'){
            $sQuery = $sQuery->where('r.added_by='.$sessionLogin->userId);
          }

          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //   echo $sQueryStr;die;
          $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

          /* Data set length after filtering */
          $sQuery->reset('limit');
          $sQuery->reset('offset');
          $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
          $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
          $iFilteredTotal = count($aResultFilterTotal);

          /* Total data set length */
          $iQuery = $sql->select()->from(array( 'r' => 'recency' ))
                        ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'));
            if($roleCode=='user'){
             $iQuery = $iQuery->where('r.added_by='.$sessionLogin->userId);
          }
            $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
            $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

          $output = array(
                  "sEcho" => intval($parameters['sEcho']),
                  "iTotalRecords" => count($iResult),
                  "iTotalDisplayRecords" => $iFilteredTotal,
                  "aaData" => array()
          );


          foreach ($rResult as $aRow) {

              $row = array();
              $row[] = $aRow['sample_id'];
              $row[] = $aRow['patient_id'];
              $row[] = ucwords($aRow['facility_name']);
              $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
              $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);

              // Control Line
               // if($aRow['control_line'] == 'present'){
               //      $controlLine = "Present(Positive/P)";
               // }
               // elseif($aRow['control_line'] == 'absent'){
               //      $controlLine = "Absent(Negative/N)";
               // }
               $row[] = ucwords($aRow['control_line']);

               // $row[] = ucwords($controlLine);

               // Positive Verification
               // if($aRow['positive_verification_line'] == 'present'){
               //      $positiveVerification = "Present(Positive/P)";
               // }
               // elseif($aRow['positive_verification_line'] == 'absent'){
               //      $positiveVerification = "Absent(Negative/N)";
               // }

               $row[] = ucwords($aRow['positive_verification_line']);

               // $row[] = ucwords($positiveVerification);

               // Long Term Verification
               // if($aRow['long_term_verification_line'] == 'present'){
               //      $longTerm = "Present(Positive/P)";
               // }
               // elseif($aRow['long_term_verification_line'] == 'absent'){
               //      $longTerm = "Absent(Negative/N)";
               // }
               // elseif($aRow['long_term_verification_line'] == ''){
               //      $longTerm = "---";
               // }

               $row[] = ucwords($aRow['long_term_verification_line']);
               // $row[] = ucwords($longTerm);


               $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                            <a class="btn btn-danger" href="/recency/edit/' . base64_encode($aRow['recency_id']) . '"><i class="si si-pencil"></i> Edit</a>
                            <a class="btn btn-primary" href="/recency/view/' . base64_encode($aRow['recency_id']) . '"><i class="si si-eye"></i> View</a>
                        </div>';

              $output['aaData'][] = $row;

          }

          return $output;
      }

    public function addRecencyDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
        $facilityDb = new FacilitiesTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $common = new CommonService();
        if( (isset($params['sampleId']) && trim($params['sampleId'])!="") || (isset($params['patientId']) && trim($params['patientId'])!="") )
        {
            if($params['facilityId']=='other'){
                $fResult = $facilityDb->checkFacilityName($params['otherFacilityName']);
                if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                    $params['facilityId'] = base64_encode($fResult['facility_id']);
                }else{
                    $facilityData = array('facility_name'=>trim($params['otherFacilityName']),
                                        'province'=>$params['location_one'],
                                        'district'=>$params['location_two'],
                                        'city'=>$params['location_three'],
                                        'status'=>'active');
                    $facilityDb->insert($facilityData);
                    if($facilityDb->lastInsertValue>0){
                    $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                    }else{
                        return false;
                    }
                }
            }
            //check oher pouplation
            if($params['riskPopulation']=='Other'){
                $rpResult = $riskPopulationDb->checkExistRiskPopulation($params['otherRiskPopulation']);
                if(isset($rpResult['name']) && $rpResult['name']!=''){
                    $params['riskPopulation'] = base64_encode($rpResult['rp_id']);
                }else{
                    $rpData = array('name'=>trim($params['otherRiskPopulation']));
                    $riskPopulationDb->insert($rpData);
                    if($riskPopulationDb->lastInsertValue>0){
                    $params['riskPopulation'] = base64_encode($riskPopulationDb->lastInsertValue);
                    }else{
                        return false;
                    }
                }
            }

            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'dob'=>($params['dob']!='')?$common->dbDateFormat($params['dob']):NULL,
                'hiv_diagnosis_date' => ($params['hivDiagnosisDate']!='')?$common->dbDateFormat($params['hivDiagnosisDate']):NULL,
                'hiv_recency_date' => (isset($params['hivRecencyDate']) && $params['hivRecencyDate']!='')?$common->dbDateFormat($params['hivRecencyDate']):NULL,
                'recency_test_performed'=>$params['recencyTestPerformed'],

                'recency_test_not_performed' => ($params['recencyTestPerformed']=='true')?$params['recencyTestNotPerformed']:NULL,
                'other_recency_test_not_performed' => ($params['recencyTestNotPerformed']=='other')?$params['otherRecencyTestNotPerformed']:NULL,

                'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                'term_outcome'=>$params['outcomeData'],
                'gender' => $params['gender'],
                'age' => $params['age'],
                'marital_status' => $params['maritalStatus'],
                'residence' => $params['residence'],
                'education_level' => $params['educationLevel'],
                'risk_population' => base64_decode($params['riskPopulation']),
                //'other_risk_population' => ($params['riskPopulation']=='Other')?$params['otherRiskPopulation']:NULL,
                'pregnancy_status' => $params['pregnancyStatus'],
                'current_sexual_partner' => $params['currentSexualPartner'],
                'past_hiv_testing' => $params['pastHivTesting'],
                'last_hiv_status' => $params['lastHivStatus'],
                'patient_on_art' => $params['patientOnArt'],
                'test_last_12_month' => $params['testLast12Month'],
                'location_one' => $params['location_one'],
                'location_two' => $params['location_two'],
                'location_three' => $params['location_three'],
                'exp_violence_last_12_month'=>$params['expViolence'],
                'notes'=>$params['comments'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId,
                'form_initiation_datetime'=> date("Y-m-d H:i:s"),
                'form_transfer_datetime'=> date("Y-m-d H:i:s"),
            );

            $this->insert($data);

            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchRecencyDetailsById($recencyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('recency')
                                ->where(array('recency_id' => $recencyId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function updateRecencyDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $logincontainer = new Container('credo');
        $common = new CommonService();


        if(isset($params['recencyId']) && trim($params['recencyId'])!="")
        {
            if($params['facilityId']=='other'){
                $fResult = $facilityDb->checkFacilityName($params['otherFacilityName']);
                if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                    $params['facilityId'] = base64_encode($fResult['facility_id']);
                }else{
                    $facilityData = array('facility_name'=>trim($params['otherFacilityName']),
                                        'province'=>$params['location_one'],
                                        'district'=>$params['location_two'],
                                        'city'=>$params['location_three'],
                                        'status'=>'active');
                    $facilityDb->insert($facilityData);
                    if($facilityDb->lastInsertValue>0){
                    $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                    }else{
                        return false;
                    }
                }
            }
            //check oher pouplation
            if($params['riskPopulation']=='Other'){
                $rpResult = $riskPopulationDb->checkExistRiskPopulation($params['otherRiskPopulation']);
                if(isset($rpResult['name']) && $rpResult['name']!=''){
                    $params['riskPopulation'] = base64_encode($rpResult['rp_id']);
                }else{
                    $rpData = array('name'=>trim($params['otherRiskPopulation']));
                    $riskPopulationDb->insert($rpData);
                    if($riskPopulationDb->lastInsertValue>0){
                    $params['riskPopulation'] = base64_encode($riskPopulationDb->lastInsertValue);
                    }else{
                        return false;
                    }
                }
            }

            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'dob' => ($params['dob']!='')?$common->dbDateFormat($params['dob']):NULL,
                'hiv_diagnosis_date' => ($params['hivDiagnosisDate']!='')?$common->dbDateFormat($params['hivDiagnosisDate']):NULL,
                'hiv_recency_date' => (isset($params['hivRecencyDate']) && $params['hivRecencyDate']!='')?$common->dbDateFormat($params['hivRecencyDate']):NULL,
                'recency_test_performed' => $params['recencyTestPerformed'],

                'recency_test_not_performed' => ($params['recencyTestPerformed']=='true')?$params['recencyTestNotPerformed']:NULL,
                'other_recency_test_not_performed' => (isset($params['recencyTestPerformed']) && $params['recencyTestPerformed']='other')?$params['otherRecencyTestNotPerformed']: NULL,

                'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                'term_outcome'=>$params['outcomeData'],
                'gender' => $params['gender'],
                'age' => $params['age'],
                'marital_status' => $params['maritalStatus'],
                'residence' => $params['residence'],
                'education_level' => $params['educationLevel'],
                'risk_population' => base64_decode($params['riskPopulation']),
                'pregnancy_status' => $params['pregnancyStatus'],
                'current_sexual_partner' => $params['currentSexualPartner'],
                'past_hiv_testing' => $params['pastHivTesting'],
                'last_hiv_status' => $params['lastHivStatus'],
                'patient_on_art' => $params['patientOnArt'],
                'test_last_12_month' => $params['testLast12Month'],
                'location_one' => $params['location_one'],
                'location_two' => $params['location_two'],
                'location_three' => $params['location_three'],
                'exp_violence_last_12_month'=>$params['expViolence'],
                'notes'=>$params['comments'],
            );
            $updateResult = $this->update($data,array('recency_id'=>$params['recencyId']));
        }
        return $updateResult;
    }

    public function fetchAllRecencyListApi($params)
    {
        $common = new CommonService();
        $config = new \Zend\Config\Reader\Ini();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                ->join(array('r' => 'recency'), 'u.user_id = r.added_by', array('*'),'left')
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name','province'),'left')
                                ->where(array('auth_token' =>$params['authToken']));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if( isset($rResult[0]['user_id']) && $rResult[0]['user_id']!='' && $rResult[0]['role_code']=='admin' ){
            $response['status']='success';
            $rececnyQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                ->join(array('r' => 'recency'), 'u.user_id = r.added_by', array('*'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name','province'));
            $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $response['recency'] = $recencyResult;
        }
        else if(isset($rResult[0]['user_id']) && $rResult[0]['user_id']!='' && $rResult[0]['status']=='active') {
            $response['status']='success';
            $rececnyQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                ->join(array('r' => 'recency'), 'u.user_id = r.added_by', array('*'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name','province'))
                                ->where(array('auth_token' =>$params['authToken']));
            $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $response['recency'] = $recencyResult;
        }
        else if($rResult['status']=='inactive'){
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        }else if($rResult['recency_id'] == ""){
            $response["status"] = "fail";
            $response["message"] = "You don't have recency data!";
        }
        else {
            $response["status"] = "fail";
            $response["message"] = "Please check your token credentials!";
        }
       return $response;
    }

    public function addRecencyDetailsApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $common = new CommonService();
        if(isset($params["form"])){
            $i = 1;
            foreach($params["form"] as $key => $recency){
                try{
                    if(isset($recency['sampleId']) && trim($recency['sampleId'])!="" || isset($recency['patientId']) && trim($recency['patientId'])!="")
                    {
                         if($recency['otherfacility']!=''){
 $fResult = $facilityDb->checkFacilityName($recency['otherfacility']);
 if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
 $recency['facilityId'] = $fResult['facility_id'];
 }else{
 $facilityData = array('facility_name'=>trim($recency['otherfacility']),
 'province'=>$recency['location_one'],
 'district'=>$recency['location_two'],
 'city'=>$recency['location_three'],
 'status'=>'active'
);
 $facilityDb->insert($facilityData);
 if($facilityDb->lastInsertValue>0){
 $recency['facilityId'] = $facilityDb->lastInsertValue;
 }
 }
 }
 //check oher pouplation
 if($recency['otherriskPopulation']!=''){
 $rpResult = $riskPopulationDb->checkExistRiskPopulation($recency['otherriskPopulation']);
 if(isset($rpResult['name']) && $rpResult['name']!=''){
 $recency['riskPopulation'] = $rpResult['rp_id'];
 }else{
 $rpData = array('name'=>trim($recency['otherriskPopulation']));
 $riskPopulationDb->insert($rpData);
 if($riskPopulationDb->lastInsertValue>0){
 $recency['riskPopulation'] = $riskPopulationDb->lastInsertValue;
 }
 }
 }

                        $userId = $recency['userId'];
                        $data = array(
                            'sample_id' => $recency['sampleId'],
                            'patient_id' => $recency['patientId'],
                            'facility_id' => $recency['facilityId'],
                            'control_line' => $recency['ctrlLine'],
                            'positive_verification_line' => $recency['positiveLine'],
                            'long_term_verification_line' => $recency['longTermLine'],
                            'gender' => $recency['gender'],
                            'latitude' => $recency['latitude'],
                            'longitude' => $recency['longitude'],
                            'age' => $recency['age'],
                            'marital_status' => $recency['maritalStatus'],
                            'residence' => $recency['residence'],
                            'education_level' => $recency['educationLevel'],
                            'risk_population' => $recency['riskPopulation'],
                            //'other_risk_population' => $recency['otherriskPopulation'],
                            'term_outcome'=>$recency['recencyOutcome'],
                            'recency_test_performed'=>$recency['testNotPerformed'],
                            'recency_test_not_performed' => ($params['testNotPerformed']=='true')?$params['recencyreason']:NULL,
                            'other_recency_test_not_performed' => (isset($params['recencyreason']) && $params['recencyreason']='other')?$params['otherreason']: NULL,
                            'pregnancy_status' => $recency['pregnancyStatus'],
                            'current_sexual_partner' => $recency['currentSexualPartner'],
                            'past_hiv_testing' => $recency['pastHivTesting'],
                            'last_hiv_status' => $recency['lastHivStatus'],
                            'patient_on_art' => $recency['patientOnArt'],
                            'test_last_12_month' => $recency['testLast12Month'],
                            'location_one' => $recency['location_one'],
                            'location_two' => $recency['location_two'],
                            'location_three' => $recency['location_three'],
                            'added_on' => date("Y-m-d H:i:s"),
                            'added_by' => $recency['userId'],
                            'exp_violence_last_12_month'=>$recency['violenceLast12Month'],
                            'mac_no'=>$recency['macAddress'],
                            'cell_phone_number'=>$recency['phoneNumber'],
                            //'ip_address'=>$recency[''],
                            'notes'=>$recency['notes'],
                            'form_initiation_datetime'=>$recency['formInitDateTime'],
                            'form_transfer_datetime'=>date("Y-m-d H:i:s"),

                        );
                        if(isset($recency['hivRecencyDate']) && trim($recency['hivDiagnosisDate'])!=""){
                            $data['hiv_diagnosis_date']=$common->dbDateFormat($recency['hivDiagnosisDate']);
                        }
                        if(isset($recency['hivRecencyDate']) && trim($recency['hivRecencyDate'])!=""){
                            $data['hiv_recency_date']=$common->dbDateFormat($recency['hivRecencyDate']);
                        }
                        if(isset($recency['dob']) && trim($recency['dob'])!=""){
                            $data['dob']=$common->dbDateFormat($recency['dob']);
                        }
                        $this->insert($data);
                        $lastInsertedId = $this->lastInsertValue;
                        if($lastInsertedId > 0){
                            $response['syncData']['response'][$key] = 'success';
                        }else{
                            $response['syncData']['response'][$key] = 'failed';
                        }
                    }
                }
                catch (Exception $exc) {
                    error_log($exc->getMessage());
                    error_log($exc->getTraceAsString());
                }
                $i++;
            }
        }else{
            try{
                if(isset($params['sampleId']) && trim($params['sampleId'])!="")
                {
                    $userId = $recency['userId'];
                    $data = array(
                        'sample_id' => $params['sampleId'],
                            'patient_id' => $params['patientId'],
                            'facility_id' => $params['facilityId'],
                            'control_line' => $params['ctrlLine'],
                            'positive_verification_line' => $params['positiveLine'],
                            'long_term_verification_line' => $params['longTermLine'],
                            'gender' => $params['gender'],
                            'latitude' => $params['latitude'],
                            'longitude' => $params['longitude'],
                            'age' => $params['age'],
                            'marital_status' => $params['maritalStatus'],
                            'residence' => $params['residence'],
                            'education_level' => $params['educationLevel'],
                            'risk_population' => $params['riskPopulation'],
                            'other_risk_population' => $params['otherriskPopulation'],
                            'term_outcome'=>$params['recencyOutcome'],
                            'pregnancy_status' => $params['pregnancyStatus'],
                            'current_sexual_partner' => $params['currentSexualPartner'],
                            'past_hiv_testing' => $params['pastHivTesting'],
                            'last_hiv_status' => $params['lastHivStatus'],
                            'patient_on_art' => $params['patientOnArt'],
                            'test_last_12_month' => $params['testLast12Month'],
                            'location_one' => $params['locationOne'],
                            'location_two' => $params['locationTwo'],
                            'location_three' => $params['locationThree'],
                            'added_on' => date("Y-m-d H:i:s"),
                            'added_by' => $params['userId'],
                            'exp_violence_last_12_month'=>$params['violenceLast12Month'],
                            'mac_no'=>$params['macAddress'],
                            'cell_phone_number'=>$params['phoneNumber'],
                            'recency_test_performed'=>$params['testNotPerformed'],
                            //'ip_address'=>$recency[''],
                            'form_initiation_datetime'=>$params['formInitDateTime'],
                            'form_transfer_datetime'=>date("Y-m-d H:i:s"),

                    );
                    if(isset($params['hivRecencyDate']) && trim($params['hivDiagnosisDate'])!=""){
                        $data['hiv_diagnosis_date']=$common->dbDateFormat($params['hivDiagnosisDate']);
                    }
                    if(isset($params['hivRecencyDate']) && trim($params['hivRecencyDate'])!=""){
                        $data['hiv_recency_date']=$common->dbDateFormat($params['hivRecencyDate']);
                    }
                    if(isset($params['dob']) && trim($params['dob'])!=""){
                        $data['dob']=$common->dbDateFormat($params['dob']);
                    }
                    $this->insert($data);
                    $lastInsertedId = $this->lastInsertValue;
                    if($lastInsertedId > 0){
                        $response['syncData']['response'] = 'success';
                    }else{
                        $response['syncData']['response'] = 'failed';
                    }
                }
            }
            catch (Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
            }
        }
        $response['syncCount']['response'] = $this->getTotalSyncCount($userId);
        return $response;
    }
     public function fetchRecencyOrderDetails($id)
          {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('r' => 'recency'))
                                      ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'),'left')
                                      ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))

                                      ->where(array('recency_id' =>$id));
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
               return $rResult;
          }
    public function getTotalSyncCount($userId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('r'=>'recency'))
                    ->columns(array("Total" => new Expression('COUNT(*)'),))
                  ->where(array('added_by'=>$userId));
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $result;
    }
}
?>
