<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

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
              $row[] = ucwords($aRow['control_line']);
              $row[] = ucwords($aRow['positive_verification_line']);
              $row[] = ucwords($aRow['long_term_verification_line']);
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

        $logincontainer = new Container('credo');
        $common = new CommonService();
        if( (isset($params['sampleId']) && trim($params['sampleId'])!="") || (isset($params['patientId']) && trim($params['patientId'])!="") )
        {
            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'hiv_diagnosis_date' => $common->dbDateFormat($params['hivDiagnosisDate']),
                'hiv_recency_date' => $common->dbDateFormat($params['hivRecencyDate']),
                'control_line' => $params['controlLine'],
                'positive_verification_line' => $params['positiveVerificationLine'],
                'long_term_verification_line' => $params['longTermVerificationLine'],
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
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId

            );

            if(isset($params['dob']) && trim($params['dob']) != ""){
                $data['dob']=$common->dbDateFormat($params['dob']);
            }else{
                $data['dob'] = 'NULL';
            }


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

        $logincontainer = new Container('credo');
        $common = new CommonService();
        if(isset($params['recencyId']) && trim($params['recencyId'])!="")
        {
            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'hiv_diagnosis_date' => $common->dbDateFormat($params['hivDiagnosisDate']),
                'hiv_recency_date' => $common->dbDateFormat($params['hivRecencyDate']),
                'control_line' => $params['controlLine'],
                'positive_verification_line' => $params['positiveVerificationLine'],
                'long_term_verification_line' => $params['longTermVerificationLine'],
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
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId
            );
            if(isset($params['dob']) && trim($params['dob']) != ""){
                $data['dob']=$common->dbDateFormat($params['dob']);
            }else{
                $data['dob']= 'NULL';
            }
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
                                ->join(array('r' => 'recency'), 'u.user_id = r.added_by', array('*'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name','province'))
                                ->where(array('auth_token' =>$params['authToken']));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        if(isset($rResult[0]['user_id']) && $rResult[0]['user_id']!='' && $rResult[0]['status']=='active') {
            $response['status']='success';
            $response['recency'] = $rResult;
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
        $common = new CommonService();
        if(isset($params["form"])){
            $i = 1;
            foreach($params["form"] as $key => $recency){
                try{
                    if(isset($recency['sampleId']) && trim($recency['sampleId'])!="")
                    {
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
                            'pregnancy_status' => $recency['pregnancyStatus'],
                            'current_sexual_partner' => $recency['currentSexualPartner'],
                            'past_hiv_testing' => $recency['pastHivTesting'],
                            'test_last_12_month' => $recency['testLast12Month'],
                            'location_one' => $recency['location_one'],
                            'location_two' => $recency['location_two'],
                            'location_three' => $recency['location_three'],
                            'added_on' => date("Y-m-d H:i:s"),
                            'added_by' => $recency['userId']
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
                            'control_line' => $recency['ctrlLine'],
                            'positive_verification_line' => $recency['positiveLine'],
                            'long_term_verification_line' => $recency['longTermLine'],
                            'gender' => $params['gender'],
                            'latitude' => $params['latitude'],
                            'longitude' => $params['longitude'],
                            'age' => $params['age'],
                            'marital_status' => $params['maritalStatus'],
                            'residence' => $params['residence'],
                            'education_level' => $params['educationLevel'],
                            'risk_population' => $params['riskPopulation'],
                            'pregnancy_status' => $params['pregnancyStatus'],
                            'current_sexual_partner' => $params['currentSexualPartner'],
                            'past_hiv_testing' => $params['pastHivTesting'],
                            'test_last_12_month' => $params['testLast12Month'],
                            'location_one' => $params['locationOne'],
                            'location_two' => $params['locationTwo'],
                            'location_three' => $params['locationThree'],
                            'added_on' => date("Y-m-d H:i:s"),
                            'added_by' => $params['userId']

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
               // $fetchResult = '';
               // $fetchResult=$this->select(array('recency_id'=>$id))->current();
               // return $fetchResult;
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('r' => 'recency'))
                                      ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'),'left')

                                      ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))

                                      ->where(array('recency_id' =>$id));
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               //echo $sQueryStr;die;
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
