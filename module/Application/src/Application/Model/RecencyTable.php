<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;
use \Application\Model\FacilitiesTable;
use \Application\Model\DistrictTable;
use \Application\Model\CityTable;

class RecencyTable extends AbstractTableGateway {

     protected $table = 'recency';
     public $vlResultOptionArray = array('target not detected','below detection line','tnd','bdl','failed','&lt; 20','&lt; 40','< 20','< 40','< 400','< 800', '<20', '<40');
     public $vlFailOptionArray = array('fail','failed');

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }

    public function randomizer($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces []= $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }     

     public function fetchRecencyDetails($parameters) {
          /* Array of database columns which should be read and sent back to DataTables. Use a space where
          * you want to insert a non-database field (for example a counter or static image)
          */
          $sessionLogin = new Container('credo');
          $queryContainer = new Container('query');
          $role = $sessionLogin->roleId;
          $roleCode = $sessionLogin->roleCode;
          $common = new CommonService();


                         
          $aColumns = array('r.sample_id','r.patient_id','DATE_FORMAT(r.sample_collection_date,"%d-%b-%Y")','DATE_FORMAT(r.sample_receipt_date,"%d-%b-%Y")','r.received_specimen_type','f.facility_name' ,'ft.facility_name','tf.testing_facility_type_name' ,'DATE_FORMAT(r.hiv_diagnosis_date,"%d-%b-%Y")','DATE_FORMAT(r.hiv_recency_date,"%d-%b-%Y")','r.control_line','r.positive_verification_line','r.long_term_verification_line','r.kit_lot_no','DATE_FORMAT(r.kit_expiry_date,"%d-%b-%Y")','r.term_outcome','r.final_outcome','r.vl_result','r.tester_name','DATE_FORMAT(r.dob,"%d-%b-%Y")','r.age','r.gender','r.marital_status','r.residence','r.education_level','rp.name','r.pregnancy_status','r.current_sexual_partner','r.past_hiv_testing','r.last_hiv_status','r.patient_on_art','r.test_last_12_month','r.exp_violence_last_12_month','DATE_FORMAT(r.form_initiation_datetime ,"%d-%b-%Y %H:%i:%s")','DATE_FORMAT(r.form_transfer_datetime ,"%d-%b-%Y %H:%i:%s")','DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")');
          $orderColumns = array('r.sample_id','r.patient_id','r.sample_collection_date','r.sample_receipt_date','r.received_specimen_type','f.facility_name','f.facility_name','ft.facility_name', 'tf.testing_facility_type_name','r.hiv_diagnosis_date','r.hiv_recency_date','r.control_line','r.positive_verification_line','r.long_term_verification_line','r.kit_lot_no','r.kit_expiry_date','r.term_outcome','r.final_outcome','r.vl_result','r.tester_name','r.dob','r.age','r.gender','r.marital_status','r.residence','r.education_level','rp.name','r.pregnancy_status','r.current_sexual_partner','r.past_hiv_testing','r.last_hiv_status','r.patient_on_art','r.test_last_12_month','r.exp_violence_last_12_month','r.form_initiation_datetime','r.form_transfer_datetime','r.vl_test_date');

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
                         $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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

                    $sQuery =   $sql->select()->from(array( 'r' => 'recency' ))
                        ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                        ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name') , 'left')
                        ->join(array('tf' => 'testing_facility_type'), 'tf.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name') , 'left')
                        ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name') , 'left')
                        ->order("r.recency_id DESC");
                    if (isset($sWhere) && $sWhere != "") {
                         $sQuery->where($sWhere);
                    }
                    if($parameters['fName']!=''){
                        $sQuery->where(array('r.facility_id'=>$parameters['fName']));
                    }
                    if($parameters['tOutcome']!=''){
                        $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
                    }
                    if($parameters['gender']!=''){
                        $sQuery->where(array('gender'=>$parameters['gender']));
                    }
                    if($parameters['finalOutcome']!=''){
                        $sQuery->where(array('final_outcome'=>$parameters['finalOutcome']));
                    }
                    if($parameters['testingFacility']!=''){
                        $sQuery->where(array('testing_facility_id'=>$parameters['testingFacility']));
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

                    $queryContainer->exportRecencyDataQuery = $sQuery;
                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                    //echo $sQueryStr;die;
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */
                    $sQuery->reset('limit');
                    $sQuery->reset('offset');
                    $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    $iFilteredTotal = count($aResultFilterTotal);

                    /* Total data set length */
                    $iQuery =   $sql->select()->from(array( 'r' => 'recency' ))
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('facility_name'),'left')
                    ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name') , 'left');

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
                         $formInitiationDate = '';
                         if($aRow['form_initiation_datetime']!='' && $aRow['form_initiation_datetime']!='0000-00-00 00:00:00' && $aRow['form_initiation_datetime']!=NULL){
                            $formInitiationAry = explode(" ",$aRow['form_initiation_datetime']);
                            $formInitiationDate = $common->humanDateFormat($formInitiationAry[0])." ".$formInitiationAry[1];
                         }
                         $formTransferDate = '';
                         if($aRow['form_transfer_datetime']!='' && $aRow['form_transfer_datetime']!='0000-00-00 00:00:00' && $aRow['form_transfer_datetime']!=NULL){
                            $formTransferAry = explode(" ",$aRow['form_transfer_datetime']);
                            $formTransferDate = $common->humanDateFormat($formTransferAry[0])." ".$formTransferAry[1];
                         }
                         $row[] = $aRow['sample_id'];
                         $row[] = $aRow['patient_id'];
                         $row[] =  $common->humanDateFormat($aRow['sample_collection_date']);
                         $row[] =  $common->humanDateFormat($aRow['sample_receipt_date']);
                         $row[] = str_replace("_"," ",ucwords($aRow['received_specimen_type']));
                         $row[] = $aRow['facility_name'];
                         $row[] = $aRow['testing_facility_name'];
                         $row[] = $aRow['testing_facility_type_name'];
                         
                         $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
                         $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);

                         $row[] = ucwords($aRow['control_line']);
                         $row[] = ucwords($aRow['positive_verification_line']);
                         $row[] = ucwords($aRow['long_term_verification_line']);
                         $row[] = $aRow['kit_lot_no'];
                         $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
                         $row[] = $aRow['term_outcome'];
                         $row[] = $aRow['final_outcome'];
                         $row[] = ucwords($aRow['vl_result']);
                         $row[] = ucwords($aRow['tester_name']);
                         $row[] = $common->humanDateFormat($aRow['dob']);
                         $row[] = $aRow['age'];
                         $row[] = ucwords($aRow['gender']);
                         $row[] = str_replace("_"," ",ucwords($aRow['marital_status']));
                         $row[] = ucwords($aRow['residence']);
                         $row[] =  str_replace("_"," ",ucwords($aRow['education_level']));
                         $row[] = ucwords($aRow['name']);
                         $row[] = str_replace("_"," ",ucwords($aRow['pregnancy_status']));
                         $row[] = str_replace("_","-",$aRow['current_sexual_partner']);
                         $row[] = ucwords($aRow['past_hiv_testing']);
                         $row[] = ucwords($aRow['last_hiv_status']);
                         $row[] = ucwords($aRow['patient_on_art']);
                         $row[] =  str_replace("_"," ",ucwords($aRow['test_last_12_month']));
                         $row[] =  str_replace("_"," ",ucwords($aRow['exp_violence_last_12_month']));
                         
                         $row[] = $formInitiationDate;
                         $row[] = $formTransferDate;
                         $row[] = $common->humanDateFormat($aRow['vl_test_date']);

                         $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                         <a class="btn btn-danger" href="/recency/edit/' . base64_encode($aRow['recency_id']) . '"><i class="si si-pencil"></i> Edit</a>
                         <a class="btn btn-primary" href="/recency/view/' . base64_encode($aRow['recency_id']) . '"><i class="si si-eye"></i> View</a>
                         <a class="btn btn-primary" href="javascript:void(0)" onclick="generatePdf('.$aRow['recency_id'].')"><i class="far fa-file-pdf"></i> PDF</a>
                         </div>';

                         $output['aaData'][] = $row;

                    }

                    return $output;
               }

                public function checkDistrictData($params)
                {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    $facilityDb = new FacilitiesTable($this->adapter);
                    $districtDb = new DistrictTable($this->adapter);
                    
                    $locationTwo = '';
                    if(!isset($params['otherDistrictName'])){
                        $params['otherDistrictName'] = $params['otherDistrict'];
                    }
                    $dResult = $facilityDb->checkDistrictName(strtolower($params['otherDistrictName']),$params['location_one']);
                    if(isset($dResult['district_name']) && $dResult['district_name']!=''){
                        $locationTwo = $dResult['district_id'];
                    }else{
                        $districtData = array(
                                'province_id'=>$params['location_one'],
                                'district_name'=>strtolower($params['otherDistrictName']),
                            );
                            $districtDb->insert($districtData);
                            if($districtDb->lastInsertValue > 0){
                                $locationTwo = $districtDb->lastInsertValue;
                            }
                    }

                    if(isset($params['facilityId']) && $params['facilityId']!='' && $locationTwo!='')
                    {
                        $facilityDb->update(array('district'=>$locationTwo),array('facility_id'=>base64_decode($params['facilityId'])));
                    }
                    return $locationTwo;
                }

               public function checkCityData($params)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    $facilityDb = new FacilitiesTable($this->adapter);
                    $cityDb = new CityTable($this->adapter);
                    
                    $locationThree = '';
                    if(!isset($params['otherCityName'])){
                        $params['otherCityName'] = $params['otherCity'];
                    }
                    $cResult = $facilityDb->checkCityName(strtolower($params['otherCityName']),$params['location_two']);
                    if(isset($cResult['city_name']) && $cResult['city_name']!=''){
                        $locationThree = $cResult['city_id'];
                    }else{
                        $cityData = array(
                                'district_id'=>$params['location_two'],
                                'city_name'=>strtolower($params['otherCityName']),
                            );
                            $cityDb->insert($cityData);
                            if($cityDb->lastInsertValue > 0){
                                $locationThree = $cityDb->lastInsertValue;
                            }
                    }
                    if(isset($params['facilityId']) && $params['facilityId']!='' && $locationThree!='')
                    {
                        $facilityDb->update(array('city'=>$locationThree),array('facility_id'=>base64_decode($params['facilityId'])));
                    }
                    return $locationThree;
               }

               public function addRecencyDetails($params)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    $logincontainer = new Container('credo');
                    $facilityDb = new FacilitiesTable($this->adapter);
                    $districtDb = new DistrictTable($this->adapter);
                    $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
                    $cityDb = new CityTable($this->adapter);
                    // $facilityTypeDb = new FacilitiesTypeTable($this->adapter);
                    $riskPopulationDb = new RiskPopulationsTable($this->adapter);
                    $common = new CommonService();
                    if( (isset($params['sampleId']) && trim($params['sampleId'])!="") || (isset($params['patientId']) && trim($params['patientId'])!="") )
                    {
                         if($params['facilityId']=='other'){
                              $fResult = $facilityDb->checkFacilityName(strtolower($params['otherFacilityName']),1);
                              if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                                   $params['facilityId'] = base64_encode($fResult['facility_id']);
                              }else{
                                    if($params['location_two']=='other'){
                                        $params['location_two'] = $this->checkDistrictData($params);
                                    }
                                    if($params['location_three']=='other'){
                                        $params['location_three'] = $this->checkCityData($params);
                                    }
                                    $facilityData = array('facility_name'=>trim($params['otherFacilityName']),
                                   'province'=>$params['location_one'],
                                   'district'=>$params['location_two'],
                                   'city'=>$params['location_three'],
                                   'facility_type_id'=>'1',
                                   'status'=>'active');
                                   $facilityDb->insert($facilityData);
                                   if($facilityDb->lastInsertValue>0){
                                        $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                                   }else{
                                        return false;
                                   }
                              }
                         }

                            if($params['location_two']=='other'){
                                $params['location_two'] = $this->checkDistrictData($params);
                            }
                            if($params['location_three']=='other'){
                                $params['location_three'] = $this->checkCityData($params);
                            }
                         
                        
                         if($params['testingFacilityId']=='other'){
                            
                                $ftResult = $facilityDb->checkFacilityName(strtolower($params['otherTestingFacility']),2);
                                if(isset($ftResult['facility_name']) && $ftResult['facility_name']!=''){
                                 $params['testingFacilityId'] = base64_encode($ftResult['facility_id']);
                                }
                                else{
                                        // echo "else2";die;
                                        $facilityData = array('facility_name'=>trim($params['otherTestingFacility']),
                                        'province'=>$params['location_one'],
                                        'district'=>$params['location_two'],
                                        'city'=>$params['location_three'],
                                        'facility_type_id'=>'2',
                                        'status'=>'active');
                                        $facilityDb->insert($facilityData);
                                        if($facilityDb->lastInsertValue>0){
                                            $params['testingFacilityId'] = base64_encode($facilityDb->lastInsertValue);
                                        }else{
                                            return false;
                                        }
                            }
                       }

                       if($params['testingModality']=='other'){

                         $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                         if(isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name']!=''){
                              $params['testingModality'] = $testftResult['testing_facility_type_id'];
                         }
                         else{
                         // echo "else2";die;
                         $testFacilityTypeData = array(
                         'testing_facility_type_name'=>$params['othertestingmodality'],
                         'testing_facility_type_status'=>'active');
                         $TestingFacilityTypeDb->insert($testFacilityTypeData);
                         if($TestingFacilityTypeDb->lastInsertValue>0){
                              $params['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
                          }else{
                              return false;
                          }
                         }
             }
                       
                        //  check oher pouplation
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
                              'testing_facility_id'=>($params['testingFacilityId']!='')?base64_decode($params['testingFacilityId']):NULL,
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
                              'final_outcome'=>$params['vlfinaloutcomeResult'],
                              
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
                              'added_on' => date("Y-m-d H:i:s"),
                              'added_by' => $logincontainer->userId,
                              'form_initiation_datetime'=> date("Y-m-d H:i:s"),
                              'form_transfer_datetime'=> date("Y-m-d H:i:s"),
                              //'kit_name'=>$params['testKitName'],
                              'kit_lot_no'=>$params['testKitLotNo'],
                              'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                              'tester_name'=>$params['testerName'],

                              'vl_test_date'=>($params['vlTestDate']!='')?$common->dbDateFormat($params['vlTestDate']):NULL,
                              //'vl_result'=>($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                              'sample_collection_date' => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate']!='')?$common->dbDateFormat($params['sampleCollectionDate']):NULL,
                              'sample_receipt_date' => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate']!='')?$common->dbDateFormat($params['sampleReceiptDate']):NULL,
                              'received_specimen_type' => $params['receivedSpecimenType'],
                              'unique_id'=>$this->randomizer(10),
                              'testing_facility_type' => $params['testingModality'],
                              
                         );
                            if($params['vlLoadResult']!=''){
                                $data['vl_result'] = $params['vlLoadResult'];
                                $date['vl_result_entry_date'] = date('Y-m-d H:i:s');
                            }else if($params['vlResultOption']){
                                $data['vl_result'] = htmlentities($params['vlResultOption']);
                                $date['vl_result_entry_date'] = date('Y-m-d H:i:s');
                            }
                         //print_r($data);die;
                      
                         //  if (strpos($params['outcomeData'], 'Long Term') !== false){
                        //       $data['final_outcome'] = 'Long Term';
                        //  }else if (strpos($params['outcomeData'], 'Invalid') !== false){
                        //       $data['final_outcome'] = 'Invalid';
                        //  }else if (strpos($params['outcomeData'], 'Negative') !== false){
                        //       $data['final_outcome'] = 'Assay Negative';
                        //  }

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

               public function fetchRecencyDetailsForPDF($recencyId)
               {
                $dbAdapter = $this->adapter;
                $sql = new Sql($dbAdapter);
                $sQuery = $sql->select()->from(array('r' => 'recency'))
                                    ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName'=>'facility_name'))
                                    ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'),'left')
                                    ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'),'left')
                                    ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'),'left')
                                    ->where(array('recency_id' => $recencyId));
                
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
               return $rResult;
               }

               public function updateRecencyDetails($params)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    $facilityDb = new FacilitiesTable($this->adapter);
                    $riskPopulationDb = new RiskPopulationsTable($this->adapter);
                    $districtDb = new DistrictTable($this->adapter);
                    $cityDb = new CityTable($this->adapter);
                    $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
                    $logincontainer = new Container('credo');
                    $common = new CommonService();

                    if(isset($params['recencyId']) && trim($params['recencyId'])!="")
                    {
                         if($params['facilityId']=='other'){
                              $fResult = $facilityDb->checkFacilityName(strtolower($params['otherFacilityName']),1);
                              if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                                   $params['facilityId'] = base64_encode($fResult['facility_id']);
                              }else{
                                
                                if($params['location_two']=='other'){
                                    $params['location_two'] = $this->checkDistrictData($params);
                                }
                                if($params['location_three']=='other'){
                                    $params['location_three'] = $this->checkCityData($params);
                                }

                                   $facilityData = array('facility_name'=>trim($params['otherFacilityName']),
                                   'province'=>$params['location_one'],
                                   'district'=>$params['location_two'],
                                   'city'=>$params['location_three'],
                                   'facility_type_id'=>'1',
                                   'status'=>'active');
                                   $facilityDb->insert($facilityData);
                                   if($facilityDb->lastInsertValue>0){
                                        $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                                   }else{
                                        return false;
                                   }
                              }
                         }

                         if($params['location_two']=='other'){
                            $params['location_two'] = $this->checkDistrictData($params);
                        }
                        if($params['location_three']=='other'){
                            $params['location_three'] = $this->checkCityData($params);
                        }


                         if($params['testingFacilityId']=='other'){
                            $ftResult = $facilityDb->checkFacilityName(strtolower($params['otherTestingFacility']),2);
                            if(isset($ftResult['facility_name']) && $ftResult['facility_name']!=''){
                             $params['testingFacilityId'] = base64_encode($ftResult['facility_id']);
                            }
                            else{
                                    $facilityData = array('facility_name'=>trim($params['otherTestingFacility']),
                                    'province'=>$params['location_one'],
                                    'district'=>$params['location_two'],
                                    'city'=>$params['location_three'],
                                    'facility_type_id'=>'2',
                                    'status'=>'active');
                                    $facilityDb->insert($facilityData);
                                    if($facilityDb->lastInsertValue>0){
                                        $params['testingFacilityId'] = base64_encode($facilityDb->lastInsertValue);
                                    }else{
                                        return false;
                                    }
                                }
                        }

                        if($params['testingModality']=='other'){

                          $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                         if(isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name']!=''){
                          $params['testingModality'] = $testftResult['testing_facility_type_id'];
                         }
                         else{
                         // echo "else2";die;
                         $testFacilityTypeData = array(
                         'testing_facility_type_name'=>$params['othertestingmodality'],
                         'testing_facility_type_status'=>'active');
                         $TestingFacilityTypeDb->insert($testFacilityTypeData);
                         if($TestingFacilityTypeDb->lastInsertValue>0){
                              $params['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
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
                              'testing_facility_id'=>($params['testingFacilityId']!='')?base64_decode($params['testingFacilityId']):NULL,
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
                              'final_outcome'=>$params['vlfinaloutcomeResult'],
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
                              'kit_lot_no'=>$params['testKitLotNo'],
                              //'kit_name'=>$params['testKitName'],
                              'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                              'tester_name'=>$params['testerName'],
                              'form_saved_datetime'=>date('Y-m-d H:i:s'),
                              'vl_test_date'=>($params['vlTestDate']!='')?$common->dbDateFormat($params['vlTestDate']):NULL,
                              //'vl_result'=>($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                              'sample_collection_date' => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate']!='')?$common->dbDateFormat($params['sampleCollectionDate']):NULL,
                              'sample_receipt_date' => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate']!='')?$common->dbDateFormat($params['sampleReceiptDate']):NULL,
                              'received_specimen_type' => $params['receivedSpecimenType'],
                              'testing_facility_type' => $params['testingModality'],
                         );
                         if($params['vlLoadResult']!=''){
                            $data['vl_result'] = $params['vlLoadResult'];
                        }else if($params['vlResultOption']){
                            $data['vl_result'] = htmlentities($params['vlResultOption']);
                        }
                        //  if (strpos($params['outcomeData'], 'Long Term') !== false)
                        //            {
                        //             $data['final_outcome'] = 'Long Term';
                        //            }
                        //            else if (strpos($params['outcomeData'], 'Invalid') !== false)
                        //            {
                        //             $data['final_outcome'] = 'Invalid';
                        //            }else if (strpos($params['outcomeData'], 'Negative') !== false)
                        //            {
                        //             $data['final_outcome'] = 'Assay Negative';
                        //            }
                         $updateResult = $this->update($data,array('recency_id'=>$params['recencyId']));
                    }
                    return $updateResult;
               }

               public function fetchAllRecencyListApi($params)
               {
                    $common = new CommonService();
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    //check the user is active or not
                    $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                    ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                    ->where(array('auth_token' =>$params['authToken']));
                    $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
                    $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    if(isset($uResult['status']) && $uResult['status']=='inactive'){
                        $response["status"] = "fail";
                        $response["message"] = "Your status is Inactive!";
                    }else if(isset($uResult['status']) && $uResult['status']=='active'){
                        $rececnyQuery = $sql->select()->from(array('r' => 'recency'))
                                    ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name','province'))
                                    ->join(array('u' => 'users'), 'u.user_id = r.added_by', array());
                                    if($uResult['role_code']!='admin'){
                                        $rececnyQuery = $rececnyQuery->where(array('u.auth_token' =>$params['authToken'],'r.added_by'=>$uResult['user_id']));
                                    }

                                    if(isset($params['start']) && isset($params['end'])){
                                        $rececnyQuery = $rececnyQuery->where(
                                            array(
                                                "((r.hiv_recency_date >='" . date("Y-m-d", strtotime($params['start'])) ."'",
                                                "r.hiv_recency_date <='" . date("Y-m-d", strtotime($params['end']))."') OR
                                                (r.hiv_recency_date is null or r.hiv_recency_date = '' or r.hiv_recency_date ='0000-00-00 00:00:00'))"
                                            )
                                        );
                                    }
                        $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
                        $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                        if(count($recencyResult) > 0){
                            $response['status']='success';
                            $response['recency'] = $recencyResult;
                        }else{
                                $response["status"] = "fail";
                                $response["message"] = "You don't have recency data!";
                        }
                    }
                    else {
                         $response["status"] = "fail";
                         $response["message"] = "Please check your token credentials!";
                    }
                    return $response;
               }

               public function fetchAllRecencyResultWithVlListApi($params)
               {
                    $common = new CommonService();
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);

                    //check the user is active or not
                    $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                    ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                    ->where(array('auth_token' =>$params['authToken']));
                    $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
                    $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    if(isset($uResult['status']) && $uResult['status']=='inactive'){
                        $response["status"] = "fail";
                        $response["message"] = "Your status is Inactive!";
                    }else if(isset($uResult['status']) && $uResult['status']=='active'){
                        $rececnyQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date','sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
                                        ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                        ->join(array('u' => 'users'), 'u.user_id = r.added_by', array())
                                        ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));
                                        if($uResult['role_code']!='admin'){
                                            $rececnyQuery = $rececnyQuery->where(array('u.auth_token' =>$params['authToken']));
                                        }
                                        if(isset($params['start']) && isset($params['end'])){
                                            $rececnyQuery = $rececnyQuery->where(array("r.hiv_recency_date >='" . date("Y-m-d", strtotime($params['start'])) ."'", "r.hiv_recency_date <='" . date("Y-m-d", strtotime($params['end']))."'"));
                                        }
                        $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
                        $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                        if(count($recencyResult) > 0){
                            $response['status']='success';
                            $response['recency'] = $recencyResult;
                       }else{
                            $response["status"] = "fail";
                            $response["message"] = "You don't have recency data!";
                       }
                    }else {
                        $response["status"] = "fail";
                        $response["message"] = "Please check your token credentials!";
                    }
                    return $response;
               }

               // Pending
               public function fetchAllPendingVlResultListApi($params)
               {
                    $common = new CommonService();
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);

                     //check the user is active or not
                    $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                                    ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                                    ->where(array('auth_token' =>$params['authToken']));
                    $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
                    $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    if(isset($uResult['status']) && $uResult['status']=='inactive'){
                        $response["status"] = "fail";
                        $response["message"] = "Your status is Inactive!";
                    }else if(isset($uResult['status']) && $uResult['status']=='active'){
                        $rececnyQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date', 'sample_id', 'term_outcome','final_outcome','vl_result'))
                                             ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                             ->join(array('u' => 'users'), 'u.user_id = r.added_by', array())
                                             ->where( "((r.vl_result IS NULL OR r.vl_result = '') AND  r.term_outcome='Assay Recent')");
                                             if($uResult['role_code']!='admin'){
                                                $rececnyQuery = $rececnyQuery->where(array('u.auth_token' =>$params['authToken']));
                                            }
                         $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
                        $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                        if(count($recencyResult) > 0){
                            $response['status']='success';
                            $response['recency'] = $recencyResult;
                        }else{
                            $response["status"] = "fail";
                            $response["message"] = "You don't have recency data!";
                        }
                    }else {
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
                    $globalDb = new GlobalConfigTable($this->adapter);
                    $districtDb = new DistrictTable($this->adapter);
                    $cityDb = new CityTable($this->adapter);
                    $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
                    $common = new CommonService();
                    if(isset($params["form"])){
                        //check user status active or not
                        $uQuery = $sql->select()->from('users')
                                        ->where(array('user_id' => $params["form"][0]['syncedBy']));
                        $uQueryStr = $sql->getSqlStringForSqlObject($uQuery); // Get the string of the Sql, instead of the Select-instance
                        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                        if(isset($uResult['status']) && $uResult['status']=='inactive'){
                            $adminEmail = $globalDb->getGlobalValue('admin_email');
                            $adminPhone = $globalDb->getGlobalValue('admin_phone');
                            $response['message'] = 'Your password has expired or has been locked, please contact your administrator('.$adminEmail.' or '.$adminPhone.')';
                            $response['status'] = 'failed';
                            return $response;
                        }
                         $i = 1;
                         foreach($params["form"] as $key => $recency){
                              try{
                                   if(isset($recency['sampleId']) && trim($recency['sampleId'])!="")
                                   {
                                        if($recency['otherfacility']!=''){
                                             $fResult = $facilityDb->checkFacilityName(strtolower($recency['otherfacility']),1);
                                             if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                                                  $recency['facilityId'] = base64_encode($fResult['facility_id']);
                                             }else{

                                                if($recency['otherDistrict']!=''){
                                                    $recency['location_two'] = $this->checkDistrictData($recency);
                                                }
                                                if($recency['otherCity']!=''){
                                                    $recency['location_three'] = $this->checkCityData($recency);
                                                }

                                                  $facilityData = array('facility_name'=>trim($recency['otherfacility']),
                                                  'province'=>$recency['location_one'],
                                                  'district'=>$recency['location_two'],
                                                  'city'=>$recency['location_three'],
                                                  'facility_type_id'=>'1',
                                                  'status'=>'active'
                                             );
                                             $facilityDb->insert($facilityData);
                                             if($facilityDb->lastInsertValue>0){
                                                  $recency['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                                             }
                                        }
                                   }else{
                                     $recency['facilityId'] = (isset($recency['facilityId']) && !empty($recency['facilityId'])) ? base64_encode($recency['facilityId']) : null;
                                   }

                                    if(isset($recency['otherDistrict']) && $recency['otherDistrict']!=''){
                                        $recency['location_two'] = $this->checkDistrictData($recency);
                                    }
                                    if(isset($recency['otherCity']) && $recency['otherCity']!=''){
                                        $recency['location_three'] = $this->checkCityData($recency);
                                    }


                                   if($recency['othertestingfacility']!=''){
                                        $fResult = $facilityDb->checkFacilityName(strtolower($recency['othertestingfacility']),2);
                                        if(isset($fResult['facility_name']) && $fResult['facility_name']!=''){
                                            $recency['testingFacility'] = $fResult['facility_id'];
                                        }else{
                                            $facilityData = array('facility_name'=>trim($recency['othertestingfacility']),
                                            'province'=>$recency['location_one'],
                                            'district'=>$recency['location_two'],
                                            'city'=>$recency['location_three'],
                                            'facility_type_id'=>'2',
                                            'status'=>'active'
                                            );
                                            $facilityDb->insert($facilityData);
                                            if($facilityDb->lastInsertValue>0){
                                                $recency['testingFacility'] = $facilityDb->lastInsertValue;
                                            }
                                        }
                                    }else{
                                        $recency['testingFacility'] = (isset($recency['testingFacility']) && !empty($recency['testingFacility'])) ? ($recency['testingFacility']) : null;
                                    }

                                    if($recency['othertestingmodality']!=''){

                                        $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                                        if(isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name']!=''){
                                         $recency['testingModality'] = $testftResult['testing_facility_type_id'];
                                        }
                                        else{
                                        // echo "else2";die;
                                        $testFacilityTypeData = array(
                                        'testing_facility_type_name'=>$recency['othertestingmodality'],
                                        'testing_facility_type_status'=>'active');
                                        $TestingFacilityTypeDb->insert($testFacilityTypeData);
                                        if($TestingFacilityTypeDb->lastInsertValue>0){
                                             $recency['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
                                         }else{
                                             return false;
                                         }
                                        }
                                        }else{
                                             $recency['testingModality'] = (isset($recency['testingModality']) && !empty($recency['testingModality'])) ? ($recency['testingModality']) : null;
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

                                   $syncedBy = $recency['syncedBy'];
                                   $data = array(
                                        'sample_id' => $recency['sampleId'],
                                        'patient_id' => $recency['patientId'],
                                        'sample_collection_date' => (isset($recency['sampleCollectionDate']) && $recency['sampleCollectionDate']!='')?$common->dbDateFormat($recency['sampleCollectionDate']):NULL,
                                        'sample_receipt_date' => (isset($recency['sampleReceiptDate']) && $recency['sampleReceiptDate']!='')?$common->dbDateFormat($recency['sampleReceiptDate']):NULL,
                                        'received_specimen_type' => $recency['receivedSpecimenType'],
                                        'facility_id' => ($recency['facilityId'] != null) ? base64_decode($recency['facilityId']) : null,
                                        'testing_facility_id'=> $recency['testingFacility'],
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
                                        'recency_test_performed'=>(isset($recency['testNotPerformed']))?$recency['testNotPerformed']:NULL,
                                        'recency_test_not_performed' => (isset($recency['testNotPerformed']) && $recency['testNotPerformed']=='true')?$recency['recencyreason']:NULL,
                                        'other_recency_test_not_performed' => (isset($recency['recencyreason']) && $recency['recencyreason']='other')?$recency['otherreason']: NULL,
                                        'pregnancy_status' => $recency['pregnancyStatus'],
                                        'current_sexual_partner' => $recency['currentSexualPartner'],
                                        'past_hiv_testing' => $recency['pastHivTesting'],
                                        'last_hiv_status' => $recency['lastHivStatus'],
                                        'patient_on_art' => $recency['patientOnArt'],
                                        'test_last_12_month' => $recency['testLast12Month'],
                                        'location_one' => $recency['location_one'],
                                        'location_two' => $recency['location_two'],
                                        'location_three' => $recency['location_three'],
                                        'added_on' => date('Y-m-d H:i:s'),
                                        'added_by' => $recency['addedBy'],
                                        'sync_by' => $recency['syncedBy'],
                                        'exp_violence_last_12_month'=>$recency['violenceLast12Month'],
                                        'mac_no'=>$recency['macAddress'],
                                        'cell_phone_number'=>$recency['phoneNumber'],
                                        //'ip_address'=>$recency[''],
                                        'notes'=>$recency['notes'],
                                        'form_initiation_datetime'=>$recency['formInitDateTime'],
                                        'app_version'=>$recency['appVersion'],
                                        'form_transfer_datetime'=>$recency['formTransferDateTime'],
                                        'form_saved_datetime'=>$recency['formSavedDateTime'],

                                        'kit_lot_no' => $recency['testKitLotNo'],
                                        //'kit_name' => $recency['testKitName'],
                                        'tester_name' => $recency['testerName'],
                                        'unique_id'=>isset($recency['unique_id']) ? $recency['unique_id']: $this->randomizer(10),
                                        'testing_facility_type' => $recency['testingModality'],
                                        //'vl_test_date'=>$recency['vlTestDate'],

                                       // 'vl_result'=>$recency['vlLoadResult'],

                                   );

                                    if($recency['vlLoadResult']!=''){
                                        $data['vl_result'] = htmlentities($recency['vlLoadResult']);
                                        $date['vl_result_entry_date'] = $recency['formSavedDateTime'];
                                    }
                                    if($recency['finalOutcome']!='')
                                    {
                                        $data['final_outcome'] = $recency['finalOutcome'];
                                    }
                                
                                    if(isset($recency['vlTestDate']) && trim($recency['vlTestDate'])!=""){
                                        $data['vl_test_date']=$common->dbDateFormat($recency['vlTestDate']);
                                    }

                                   if(isset($recency['hivDiagnosisDate']) && trim($recency['hivDiagnosisDate'])!=""){
                                        $data['hiv_diagnosis_date']=$common->dbDateFormat($recency['hivDiagnosisDate']);
                                   }
                                   if(isset($recency['hivRecencyDate']) && trim($recency['hivRecencyDate'])!=""){
                                        $data['hiv_recency_date']=$common->dbDateFormat($recency['hivRecencyDate']);
                                   }
                                   if(isset($recency['dob']) && trim($recency['dob'])!=""){
                                        $data['dob']=$common->dbDateFormat($recency['dob']);
                                   }
                                   if(isset($recency['testKitExpDate']) && trim($recency['testKitExpDate'])!=""){
                                        $data['kit_expiry_date']=$common->dbDateFormat($recency['testKitExpDate']);
                                   }
                                //    if (strpos($recency['recencyOutcome'], 'Long Term') !== false)
                                //    {
                                //         $data['final_outcome'] = 'Long Term';
                                //    }else if (strpos($recency['recencyOutcome'], 'Invalid') !== false)
                                //    {
                                //         $data['final_outcome'] = 'Invalid';
                                //    }else if (strpos($recency['recencyOutcome'], 'Negative') !== false)
                                //    {
                                //         $data['final_outcome'] = 'Assay Negative';
                                //    }

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
                              $syncedBy = $recency['syncedBy'];
                              $data = array(
                                   'sample_id' => $params['sampleId'],
                                   'patient_id' => $params['patientId'],
                                   'facility_id' => (isset($params['facilityId']) && !empty($params['facilityId'])) ? ($params['facilityId']) : null,
                                   'testing_facility_id' => (isset($params['testingFacility']) && !empty($params['testingFacility'])) ? ($params['testingFacility']) : null,
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
                                   'added_on' => $params['addedOn'],
                                    'added_by' => $params['addedBy'],
                                    'sync_by' => $params['syncedBy'],
                                   'exp_violence_last_12_month'=>$params['violenceLast12Month'],
                                   'mac_no'=>$params['macAddress'],
                                   'cell_phone_number'=>$params['phoneNumber'],
                                   'recency_test_performed'=>$params['testNotPerformed'],
                                   'app_version'=>$recency['appVersion'],
                                   //'ip_address'=>$recency[''],
                                   'form_initiation_datetime'=>$params['formInitDateTime'],
                                   'form_transfer_datetime'=>date("Y-m-d H:i:s"),
                                   //'kit_name' => $params['testKitName'],
                                   'kit_lot_no' => $params['testKitLotNo'],
                                   'tester_name' => $params['testerName'],
                                   'unique_id'=>$this->randomizer(10),
                                   'vl_result'=>$params['vlLoadResult'],
                                   'sample_collection_date' => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate']!='')?$common->dbDateFormat($params['sampleCollectionDate']):NULL,
                                   'sample_receipt_date' => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate']!='')?$common->dbDateFormat($params['sampleReceiptDate']):NULL,
                                   'received_specimen_type' => $params['receivedSpecimenType'],
                                   'testing_facility_type' => $params['testingModality'],
                              );

                              if(isset($params['vlTestDate']) && trim($params['vlTestDate'])!=""){
                                $data['vl_test_date']=$common->dbDateFormat($params['vlTestDate']);
                              }

                              if(isset($params['hivRecencyDate']) && trim($params['hivDiagnosisDate'])!=""){
                                   $data['hiv_diagnosis_date']=$common->dbDateFormat($params['hivDiagnosisDate']);
                              }
                              if(isset($params['hivRecencyDate']) && trim($params['hivRecencyDate'])!=""){
                                   $data['hiv_recency_date']=$common->dbDateFormat($params['hivRecencyDate']);
                              }
                              if(isset($params['dob']) && trim($params['dob'])!=""){
                                   $data['dob']=$common->dbDateFormat($params['dob']);
                              }
                              if(isset($params['testKitExpDate']) && trim($params['testKitExpDate'])!=""){
                                   $data['kit_expiry_date']=$common->dbDateFormat($params['testKitExpDate']);
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
               if($syncedBy!=''){
                $response['syncCount']['response'] = $this->getTotalSyncCount($syncedBy);
               }else{
                $response['syncCount']['response'] = 0;
               }
               return $response;
          }
          public function fetchRecencyOrderDetails($id)
          {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('r' => 'recency'))
                            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName'=>'facility_name'))
                            ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'),'left')
                            ->join(array('pr' => 'province_details'), 'pr.province_id = f.province', array('province_name'),'left')
                            ->join(array('dt' => 'district_details'), 'dt.district_id = f.district', array('district_name'),'left')
                            ->join(array('cd' => 'city_details'), 'cd.city_id = f.city', array('city_name'),'left')
               ->where(array('recency_id' =>$id));
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
               return $rResult;
          }
          public function getTotalSyncCount($syncedBy)
          {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);
               $query = $sql->select()->from(array('r'=>'recency'))
               ->columns(array("Total" => new Expression('COUNT(*)'),))
               ->where(array('sync_by'=>$syncedBy));
               $queryStr = $sql->getSqlStringForSqlObject($query);
               $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
               return $result;
          }

          public function getActiveTester($strSearch) {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('r'=>'recency'))
               ->columns(array('tester_name'))
               ->where('(tester_name like "%'.$strSearch.'%" OR tester_name like "%'.$strSearch.'%")')
               ->limit('100');

               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               // \Zend\Debug\Debug::dump($result);die;

               $echoResult = array();
               foreach ($rResult as $row) {
                    $echoResult[] = ucwords($row['tester_name']);
               }

               if (count($echoResult) == 0) {
                    $echoResult[] =  $strSearch;
               }
               // return array("result" => $echoResult);
               return $echoResult;
          }

          public function fetchTesterNameAllDetailsApi()
          {
               $recencyResultObject='';
               $recencyResultObject= $this->select()->toArray();
               $testerNameList=[];
               foreach ($recencyResultObject as $recencyTestResult) {
                    $testerNameList[]=$recencyTestResult['tester_name'];
               }

               $response['status'] = 'success' ;
               $response['config'] = $testerNameList;
               return $response;
          }

          public function fetchSampleData($params)
          {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_date', 'term_outcome', 'vl_result', 'final_outcome'))
                         ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                         ->where(array('r.term_outcome'=>'Assay Recent'));
                         if(isset($params['province']) && $params['province']!=''){
                            $sQuery = $sQuery->where(array('f.province'=>$params['province']));
                         }
                         if(isset($params['district']) && $params['district']!=''){
                            $sQuery = $sQuery->where(array('f.district'=>$params['district']));
                         }
                         if(isset($params['city']) && $params['city']!=''){
                            $sQuery = $sQuery->where(array('f.city'=>$params['city']));
                         }
                         if(isset($params['facility']) && $params['facility']!=''){
                            $sQuery = $sQuery->where(array('r.vl_test_date'=>$params['vlTestDate']));
                         }
                         if(isset($params['onloadData']) && $params['onloadData']=='yes'){
                            $sQuery = $sQuery->where(array('r.vl_result is null OR r.vl_result=""'));
                         }
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            return $rResult;
          }

          public function updateVlSampleResult($params)
          {
              //\Zend\Debug\Debug::dump($params);die;
               $common = new CommonService();
               $sampleVlResult = explode(",",$params['vlResult']);
               $sampleVlResultId = explode(",",$params['vlResultRowId']);
               $dataOutcome = explode(",",$params['vlDataOutCome']);


               foreach($sampleVlResult as $key=>$result){
                
                    $data = array(
                         'vl_result'=>$result,
                         'vl_test_date'=>$common->dbDateFormat($params['vlTestDate'][$key]),
                         'vl_result_entry_date'=>date('Y-m-d H:i:s')
                    );

                    if((in_array(strtolower($result),$this->vlFailOptionArray))){
                        $data['final_outcome'] = 'Inconclusive';
                    } else if((in_array(strtolower($result),$this->vlResultOptionArray))){
                        $data['final_outcome'] = 'Long Term';
                    } else if ($result > 1000) {
                        $data['final_outcome'] = 'RITA Recent';
                    } else if ($result <= 1000) {
                        $data['final_outcome'] = 'Long Term';
                    }
                    
                    $this->update($data,array('recency_id'=>str_replace('vlResultOption','',$sampleVlResultId[$key])));
               }
          }

          public function fetchAllRecencyResultWithVlList($parameters)
          {

             /* Array of database columns which should be read and sent back to DataTables. Use a space where
            * you want to insert a non-database field (for example a counter or static image)
            */
            $queryContainer = new Container('query');
            $common = new CommonService();

            $aColumns = array('DATE_FORMAT(r.hiv_recency_date,"%d-%b-%Y")','r.sample_id','DATE_FORMAT(r.sample_collection_date,"%d-%b-%Y")','DATE_FORMAT(r.sample_receipt_date,"%d-%b-%Y")','r.received_specimen_type','r.term_outcome','r.final_outcome','f.facility_name','ft.facility_name','r.vl_result', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")');
            $orderColumns = array('r.hiv_recency_date','r.sample_id','r.sample_collection_date','r.sample_receipt_date','r.received_specimen_type','r.term_outcome','r.final_outcome','f.facility_name','ft.facility_name','r.vl_result','r.vl_test_date');

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
                            $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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

                    $sQuery =   $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date','sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date','sample_collection_date','sample_receipt_date','received_specimen_type'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                                ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

                    if (isset($sWhere) && $sWhere != "") {
                         $sQuery->where($sWhere);
                    }
                    if($parameters['fName']!=''){
                        $sQuery->where(array('r.facility_id'=>$parameters['fName']));
                    }
                    if($parameters['testingFacility']!=''){
                        $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
                    }
                    if($parameters['locationOne']!=''){
                        $sQuery = $sQuery->where(array('province'=>$parameters['locationOne']));
                        if($parameters['locationTwo']!=''){
                              $sQuery = $sQuery->where(array('district'=>$parameters['locationTwo']));
                        }
                        if($parameters['locationThree']!=''){
                              $sQuery = $sQuery->where(array('city'=>$parameters['locationThree']));
                        }
                  }

                    if (isset($sOrder) && $sOrder != "") {
                         $sQuery->order($sOrder);
                    }

                    if (isset($sLimit) && isset($sOffset)) {
                         $sQuery->limit($sLimit);
                         $sQuery->offset($sOffset);
                    }
                    $queryContainer->exportRecentResultDataQuery = $sQuery;
                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                     //echo $sQueryStr;die;
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */
                    $sQuery->reset('limit');
                    $sQuery->reset('offset');
                    $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    $iFilteredTotal = count($aResultFilterTotal);

                    /* Total data set length */
                    $iQuery =   $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date','sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                                ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

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

                         $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);
                         $row[] = $aRow['sample_id'];
                         $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
                         $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
                         $row[] = ucwords(str_replace('_', ' ', $aRow['received_specimen_type']));
                         $row[] = $aRow['term_outcome'];
                         $row[] = $aRow['final_outcome'];
                         $row[] = $aRow['facility_name'];
                         $row[] = $aRow['testing_facility_name'];
                         $row[] = $aRow['vl_result'];
                         $row[] = $common->humanDateFormat($aRow['vl_test_date']);

                         $output['aaData'][] = $row;
                    }
                    return $output;
          }

          public function fetchAllLtResult($parameters)
          {

             /* Array of database columns which should be read and sent back to DataTables. Use a space where
            * you want to insert a non-database field (for example a counter or static image)
            */
            $queryContainer = new Container('query');
            $common = new CommonService();

            $aColumns = array('DATE_FORMAT(r.hiv_recency_date,"%d-%b-%Y")','r.sample_id','r.term_outcome','r.final_outcome','f.facility_name','ft.facility_name','r.vl_result', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")');
            $orderColumns = array('r.hiv_recency_date','r.sample_id','r.term_outcome','r.final_outcome','f.facility_name','ft.facility_name','r.vl_result','r.vl_test_date');

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
                            $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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

                    $sQuery =   $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date','sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date','sample_collection_date','sample_receipt_date','received_specimen_type'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                                ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%Long Term%')));

                    if (isset($sWhere) && $sWhere != "") {
                         $sQuery->where($sWhere);
                    }
                    if($parameters['fName']!=''){
                        $sQuery->where(array('r.facility_id'=>$parameters['fName']));
                    }
                    if($parameters['locationOne']!=''){
                        $sQuery = $sQuery->where(array('province'=>$parameters['locationOne']));
                        if($parameters['locationTwo']!=''){
                              $sQuery = $sQuery->where(array('district'=>$parameters['locationTwo']));
                        }
                        if($parameters['locationThree']!=''){
                              $sQuery = $sQuery->where(array('city'=>$parameters['locationThree']));
                        }
                  }
                  if($parameters['testingFacility']!=''){
                    $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
                  }

                    if (isset($sOrder) && $sOrder != "") {
                         $sQuery->order($sOrder);
                    }

                    if (isset($sLimit) && isset($sOffset)) {
                         $sQuery->limit($sLimit);
                         $sQuery->offset($sOffset);
                    }
                    $queryContainer->exportLongtermDataQuery = $sQuery;
                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

                    // echo $sQueryStr;die;
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */
                    $sQuery->reset('limit');
                    $sQuery->reset('offset');
                    $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    $iFilteredTotal = count($aResultFilterTotal);

                    /* Total data set length */
                    $iQuery =   $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_date','sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                                ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                                ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%Long Term%')));

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

                         $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);
                         $row[] = $aRow['sample_id'];
                         $row[] = $aRow['term_outcome'];
                         $row[] = $aRow['final_outcome'];
                         $row[] = $aRow['facility_name'];
                         $row[] = $aRow['testing_facility_name'];
                         $row[] = $aRow['vl_result'];
                         $row[] = $common->humanDateFormat($aRow['vl_test_date']);

                         $output['aaData'][] = $row;
                    }
                    return $output;
          }

          public function fetchTatReportAPI($params)
          {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            //check the user is active or not
            $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id','status'))
                        ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
                        ->where(array('auth_token' =>$params['authToken']));
            $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
            $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if(isset($uResult['status']) && $uResult['status']=='inactive'){
                $response["status"] = "fail";
                $response["message"] = "Your status is Inactive!";
            }else if(isset($uResult['status']) && $uResult['status']=='active'){
                $sQuery = $sql->select()->from(array('r' => 'recency'))
                            ->columns(array(
                                'sample_id','final_outcome',"hiv_recency_date" => new Expression("DATE_FORMAT(DATE(hiv_recency_date), '%d-%b-%Y')"),'vl_test_date'=> new Expression("DATE_FORMAT(DATE(vl_test_date), '%d-%b-%Y')"),'vl_result_entry_date'=> new Expression("DATE_FORMAT(DATE(vl_result_entry_date), '%d-%b-%Y')"),
                                "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_date))) AS DECIMAL (10))")
                            ))
                            ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_date!="" AND vl_test_date!=""'))
                            ->group('recency_id');
                            if(isset($params['start']) && isset($params['end'])){
                                $sQuery = $sQuery->where(array("r.hiv_recency_date >='" . date("Y-m-d", strtotime($params['start'])) ."'", "r.hiv_recency_date <='" . date("Y-m-d", strtotime($params['end']))."'"));
                            }
                            if($uResult['role_code']!='admin'){
                                $sQuery = $sQuery->where(array('u.auth_token' =>$params['authToken']));
                            }
                $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                if(count($rResult) > 0){
                    $response['status']='success';
                    $response['tat'] = $rResult;
               }else{
                    $response["status"] = "fail";
                    $response["message"] = "You don't have TAT data!";
               }
            }else {
                $response["status"] = "fail";
                $response["message"] = "Please check your token credentials!";
            }
            return $response;
          }

        public function fetchTatReport($parameters)
        {
 /* Array of database columns which should be read and sent back to DataTables. Use a space where
            * you want to insert a non-database field (for example a counter or static image)
            */
            $queryContainer = new Container('query');
            $common = new CommonService();

            $aColumns = array('r.sample_id','ft.facility_name','r.final_outcome','DATE_FORMAT(r.hiv_recency_date,"%d-%b-%Y")','DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")','DATE_FORMAT(r.vl_result_entry_date,"%d-%b-%Y")');
            $orderColumns = array('r.sample_id','ft.facility_name','r.final_outcome','r.hiv_recency_date','r.vl_test_date','r.vl_result_entry_date');

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
                            $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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

                    $sQuery =   $sql->select()->from(array('r' => 'recency'))
                    ->columns(array(
                        'sample_id','final_outcome',"hiv_recency_date",'vl_test_date','vl_result_entry_date','sample_collection_date','sample_receipt_date','received_specimen_type',
                        "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_date))) AS DECIMAL (10))")
                    ))
                    
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                    ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_date!="" AND vl_test_date!=""'))
                    ->group('recency_id');
                    // if(isset($params['start']) && isset($params['end'])){
                    //     $sQuery = $sQuery->where(array("r.hiv_recency_date >='" . date("Y-m-d", strtotime($params['start'])) ."'", "r.hiv_recency_date <='" . date("Y-m-d", strtotime($params['end']))."'"));
                    // }

                    if($parameters['testingFacility']!=''){
                        $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
                    }
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
                    $queryContainer->exportTatQuery = $sQuery;
                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

                    // echo $sQueryStr;die;data
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */
                    $sQuery->reset('limit');
                    $sQuery->reset('offset');
                    $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    $iFilteredTotal = count($aResultFilterTotal);

                    /* Total data set length */
                    $iQuery =   $sql->select()->from(array('r' => 'recency'))
                    ->columns(array(
                        'sample_id','final_outcome',"hiv_recency_date",'vl_test_date','vl_result_entry_date',
                        "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_date))) AS DECIMAL (10))")
                    ))
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                    ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_date!="" AND vl_test_date!=""'))
                    ->group('recency_id');

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
                        $row[] = $aRow['testing_facility_name'];
                        $row[] = $aRow['final_outcome'];
                        $row[] = $common->humanDateFormat($aRow['hiv_recency_date']);
                        $row[] = $common->humanDateFormat($aRow['vl_test_date']);
                        $row[] = date('d-M-Y',strtotime($aRow['vl_result_entry_date']));
                        $row[] = $aRow['diffInDays'];
                        $output['aaData'][] = $row;
                    }
                    return $output;
        }

        public function fetchSampleResult($params)
        {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_date', 'term_outcome', 'vl_result', 'final_outcome'))
                        ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                        ->where(array('vl_result!="" AND vl_result is not null AND mail_sent_status is null'));
                        if($params['locationOne']!=''){
                            $sQuery = $sQuery->where(array('province'=>$params['locationOne']));
                            if($params['locationTwo']!=''){
                                  $sQuery = $sQuery->where(array('district'=>$params['locationTwo']));
                            }
                            if($params['locationThree']!=''){
                                  $sQuery = $sQuery->where(array('city'=>$params['locationThree']));
                            }
                        }
                        if($params['facilityId']!=''){
                            $sQuery = $sQuery->where(array('r.facility_id'=>$params['facilityId']));
                        }
                         
                         
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            return $rResult;
        }

        public function fetchEmailSendResult($params)
        {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('r' => 'recency'))
                            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                         ->where("recency_id IN('".$params['selectedSampleId']."')");
                         
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            return $rResult;
        }

        public function updateEmailSendResult($params)
        {
            $tempDb = new \Application\Model\TempMailTable($this->adapter);

            $config = new \Zend\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');            

            $emailFormField = json_decode($params['emailResultFields'],true);
            $to = $emailFormField['toEmail'];
            $subject = $emailFormField['subject'];
            $message = $emailFormField['message'];
            $fromName = 'HIV Recency Testing';
            $attachment = $params['pdfFile'];
            $fromMail  = $configResult["email"]["config"]["username"];
            $mailResult = 0;
            $mailResult = $tempDb->insertTempMailDetails($to, $subject, $message, $fromMail, $fromName,$cc=null,$bcc=null,$attachment);
            if($mailResult>0)
            {
                foreach($emailFormField['to'] as $recencyId)
                {
                    $this->update(array('mail_sent_status'=>'yes'),array('recency_id'=>$recencyId));
                }
            }
            return $mailResult;
        }

        public function updateOutcome()
        {
            //first check termoutcome avialable or not
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $sQuery = $sql->select()->from(array('r' => 'recency'))
                            ->columns(array('recency_id','control_line','positive_verification_line','long_term_verification_line'))
                            ->where(array('control_line!="" AND positive_verification_line!="" AND long_term_verification_line!="" AND (term_outcome="" OR term_outcome IS NULL)'));
                         
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            //update assay outcome
            if(count($rResult)>0)
            {
                foreach($rResult as $outcome)
                {
                    $this->updateTermOutcome($outcome);
                }
            }

            //second check final outcome
            $fQuery = $sql->select()->from(array('r' => 'recency'))
                            ->columns(array('recency_id','term_outcome','vl_result'))
                            ->where(array('vl_result!="" AND term_outcome="Assay Recent" AND (final_outcome="" OR final_outcome IS NULL)'));
                         
            $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
            $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if(count($fResult)>0)
            {
                foreach($fResult as $fOutCome)
                {
                    $this->updateFinalOutcome($fOutCome);
                }
            }
        }
    
        //refer updateOutcome Function
        public function updateFinalOutcome($fOutCome)
        {
            $recencyId = $fOutCome['recency_id'];

            if((in_array(strtolower($fOutCome['vl_result']),$this->vlFailOptionArray))){
                $data['final_outcome'] = 'Inconclusive';
            } else if((in_array(strtolower($fOutCome['vl_result']),$this->vlResultOptionArray))){
                $data['final_outcome'] = 'Long Term';
            } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] > 1000) {
                $data['final_outcome'] = 'RITA Recent';
            } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] <= 1000) {
                $data['final_outcome'] = 'Long Term';
            }
           $this->update($data,array('recency_id'=>$recencyId));
        }

        //refer updateOutcome Function
        public function updateTermOutcome($outcome)
        {
            $controlLine = $outcome['control_line'];
            $positiveControlLine = $outcome['positive_verification_line'];
            $longControlLine = $outcome['long_term_verification_line'];
            $recencyId = $outcome['recency_id'];
            if(
                ($controlLine=='absent' && $positiveControlLine=='absent' && $longControlLine=='absent')
                || ($controlLine=='absent' && $positiveControlLine=='absent' && $longControlLine=='present')
                || ($controlLine=='absent' && $positiveControlLine=='present' && $longControlLine=='absent')
                || ($controlLine=='absent' && $positiveControlLine=='present' && $longControlLine=='present')
                || ($controlLine=='present' && $positiveControlLine=='absent' && $longControlLine=='present')
            )
            {
                $this->update(array('term_outcome'=>'Invalid â€“ Please Verify'),array('recency_id'=>$recencyId));
            }else if($controlLine=='present' && $positiveControlLine=='absent' && $longControlLine=='absent'){
                $this->update(array('term_outcome'=>'Assay Negative'),array('recency_id'=>$recencyId));
            }else if($controlLine=='present' && $positiveControlLine=='present' && $longControlLine=='absent'){
                $this->update(array('term_outcome'=>'Assay Recent'),array('recency_id'=>$recencyId));
            }else if($controlLine=='present' && $positiveControlLine=='present' && $longControlLine=='present'){
                $this->update(array('term_outcome'=>'Long Term'),array('recency_id'=>$recencyId));
            }
        }

        public function vlsmSync($sm)
        {
            $dbTwoAdapter = $sm->get('db1');
            $sql1 = new Sql($dbTwoAdapter);

            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);

            $fQuery = $sql->select()->from(array('r' => 'recency'))
                            ->columns(array('recency_id','term_outcome','vl_result','sample_id'))
                            ->where(array('(vl_result="" OR vl_result IS NULL)  AND term_outcome="Assay Recent"'));
                         
            $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
            $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            if(count($fResult)>0)
            {
                foreach($fResult as $data)
                {
                    $fQuery = $sql1->select()->from(array('vl' => 'vl_request_form'))
                            ->columns(array('result','sample_code'))
                            ->where(array('sample_code'=>$data['sample_id']));
                         
                    $fQueryStr = $sql1->getSqlStringForSqlObject($fQuery);
                    $fResult = $dbTwoAdapter->query($fQueryStr, $dbTwoAdapter::QUERY_MODE_EXECUTE)->current();
                    
                    if(isset($fResult['result']) && $fResult['result']!='')
                    {
                        $this->update(array('vl_result'=>$fResult['result']),array('recency_id'=>$data['recency_id']));
                        
                        $this->updateFinalOutcome($data);
                    }
                }
            }
        }

        public function getWeeklyReport($params)
        {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $queryContainer = new Container('query');
            $general = new CommonService();

            if(isset($params['samplesCollectionDate']) && trim($params['samplesCollectionDate'])!= ''){
                $s_c_date = explode("to", $_POST['samplesCollectionDate']);
                if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                     $start_date = $general->dbDateFormat(trim($s_c_date[0]));
                }
                if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                     $end_date = $general->dbDateFormat(trim($s_c_date[1]));
                }
            }

            $rQuery = $sql->select()->from(array('r'=>'recency'))
                                ->columns(
                                          array(
                                            "Samples Received" => new Expression("COUNT(*)"),
                                            "Samples Collected" => new Expression("SUM(CASE 
                                                                                WHEN (((r.sample_collection_date is NOT NULL AND r.sample_collection_date !=''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Samples Pending to be Tested" => new Expression("SUM(CASE 
                                                                                WHEN (((r.hiv_recency_date is NULL OR r.hiv_recency_date =''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Samples Tested" => new Expression("SUM(CASE 
                                                                                WHEN (((r.hiv_recency_date is NOT NULL AND r.hiv_recency_date !=''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Assay Recent" => new Expression("SUM(CASE 
                                                                                    WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                                                                    ELSE 0
                                                                                    END)"),
                                            "Long Term" => new Expression("SUM(CASE 
                                                                                WHEN ((term_outcome='Long Term' OR term_outcome='long term')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Assay Negative" => new Expression("SUM(CASE 
                                                                                WHEN ((term_outcome='Assay Negative' OR term_outcome='assay negative')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "VL Done" => new Expression("SUM(CASE 
                                                                                WHEN ((vl_result!='' AND vl_result is NOT NULL)) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "VL Pending" => new Expression("SUM(CASE 
                                                                                WHEN ((vl_result='' OR vl_result is NULL)) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "RITA Recent" => new Expression("SUM(CASE 
                                                                                WHEN ((final_outcome='RITA Recent' OR final_outcome='RITA recent')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Long Term Final" => new Expression("SUM(CASE 
                                                                                WHEN ((final_outcome='Long Term' OR final_outcome='long term')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                            "Inconclusive" => new Expression("SUM(CASE 
                                                                                WHEN ((final_outcome='Inconclusive' OR final_outcome='inconclusive')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                                          )
                                        );
                                        
                                        if($params['samplesCollectionDate']!=''){
                                            $rQuery = $rQuery->where(array("r.sample_collection_date >='" . $start_date ."'", "r.sample_collection_date <='" . $end_date."'"));
                                        }
                                        if($params['testingFacility']!='')
                                        {
                                            $rQuery = $rQuery->where(array('r.testing_facility_id'=>$params['testingFacility']));
                                        }
           $queryContainer->exportWeeklyDataQuery = $rQuery;
           $rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
           $fResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
           return $fResult;
        }
        
        
        public function fetchAllRecencyResult($parameters)
          {
             /* Array of database columns which should be read and sent back to DataTables. Use a space where
            * you want to insert a non-database field (for example a counter or static image)
            */
            $queryContainer = new Container('query');
            $common = new CommonService();
            $general = new CommonService();
            $aColumns = array('f.facility_name','ft.facility_name','totalSamples','samplesReceived','samplesRejected','samplesTestedRecency', 'samplesTestedViralLoad','samplesFinalOutcome');
            $orderColumns = array('f.facility_name','ft.facility_name','totalSamples','samplesReceived','samplesRejected','samplesTestedRecency', 'samplesTestedViralLoad','samplesFinalOutcome');

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
                            $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ( $parameters['sSortDir_' . $i] ) . ",";
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
                    $totalSamples = array();
                    $sQuery =   $sql->select()->from(array('r' => 'recency'))
                    ->columns(
                         array(
                         "totalSamples" => new Expression('COUNT(*)'),
                         "samplesReceived" => new Expression("SUM(CASE 
                                                               WHEN (((r.sample_receipt_date is NOT NULL) )) THEN 1
                                                               ELSE 0
                                                               END)"),
                         "samplesRejected" => new Expression("SUM(CASE 
                                                                 WHEN (((r.recency_test_not_performed ='sample_rejected') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                         "samplesTestedRecency" => new Expression("SUM(CASE 
                                                                 WHEN (((r.term_outcome='Assay Recent') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                         "samplesTestedViralLoad" => new Expression("SUM(CASE 
                                                                 WHEN (((r.vl_test_date is NOT NULL) )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                         "samplesFinalOutcome" => new Expression("SUM(CASE 
                                                                 WHEN (((r.final_outcome is NOT NULL) )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                                                                                                         
                         )
                         )
                    ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                    ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'),'left')
                    ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'),'left')
                    ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'),'left')
                    ->group('r.facility_id');
                    //->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

                    if (isset($sWhere) && $sWhere != "") {
                         $sQuery->where($sWhere);
                    }
                    if($parameters['fName']!=''){
                         $sQuery->where(array('r.facility_id'=>$parameters['fName']));
                     }
                     if($parameters['testingFacility']!=''){
                         $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
                     }
                     if($parameters['locationOne']!=''){
                         $sQuery = $sQuery->where(array('p.province_id'=>$parameters['locationOne']));
                         if($parameters['locationTwo']!=''){
                               $sQuery = $sQuery->where(array('d.district_id'=>$parameters['locationTwo']));
                         }
                         if($parameters['locationThree']!=''){
                               $sQuery = $sQuery->where(array('c.city_id'=>$parameters['locationThree']));
                         }
                   }
                   if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
                    $s_c_date = explode("to", $_POST['sampleTestedDates']);
                    if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                         $start_date = $general->dbDateFormat(trim($s_c_date[0]));
                    }
                    if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                         $end_date = $general->dbDateFormat(trim($s_c_date[1]));
                    }
                }

                if($parameters['sampleTestedDates']!=''){
                    $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date ."'", "r.sample_collection_date <='" . $end_date."'"));
                }

                if($parameters['tOutcome']!=''){
                    $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
                }
             
                if($parameters['finalOutcome']!=''){
                    $sQuery->where(array('final_outcome'=>$parameters['finalOutcome']));
                }

                    if (isset($sOrder) && $sOrder != "") {
                         $sQuery->order($sOrder);
                    }

                    if (isset($sLimit) && isset($sOffset)) {
                         $sQuery->limit($sLimit);
                         $sQuery->offset($sOffset);
                    }
                    $queryContainer->exportRecencyDataResultDataQuery = $sQuery;
                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
                     //echo $sQueryStr;die;
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */
                    $sQuery->reset('limit');
                    $sQuery->reset('offset');
                    $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
                    $iFilteredTotal = count($aResultFilterTotal);

                    /* Total data set length */
                    $iQuery =   $sql->select()->from(array('r' => 'recency'))
                   
                    ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
                    ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'),'left')
                                    ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'),'left')
                                    ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'),'left')
                    ->group('r.facility_id');

                    

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

                         $row[] = $aRow['facility_name'];
                         $row[] = $aRow['testing_facility_name'];
                         $row[] = $aRow['totalSamples'];
                         $row[] = $aRow['samplesReceived'];
                         $row[] = $aRow['samplesRejected'];
                         $row[] = $aRow['samplesTestedRecency'];
                         $row[] = $aRow['samplesTestedViralLoad'];
                         $row[] = $aRow['samplesFinalOutcome'];

                         $output['aaData'][] = $row;
                    }
                    return $output;
          }

          
          public function fetchRecencyAllDataCount($parameters)
          {
              $dbAdapter = $this->adapter;
              $sql = new Sql($dbAdapter);
              $general = new CommonService();
              $sQuery =   $sql->select()->from(array('r' => 'recency'))
              ->columns(
                   array(
                   "totalSamples" => new Expression('COUNT(*)'),
                   "samplesTestedRecency" => new Expression("SUM(CASE 
                                                           WHEN (((r.term_outcome='Assay Recent') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                   "samplesTestedViralLoad" => new Expression("SUM(CASE 
                                                           WHEN (((r.vl_test_date is NOT NULL) )) THEN 1
                                                           ELSE 0
                                                           END)"),
                   "samplesFinalOutcome" => new Expression("SUM(CASE 
                                                           WHEN (((r.final_outcome='RITA Recent') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                                                                                                   
                   )
                   )
              ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'),'left')
              ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
              ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'),'left')
              ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'),'left')
              ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'),'left');

              if($parameters['fName']!=''){
               $sQuery->where(array('r.facility_id'=>$parameters['fName']));
           }
           if($parameters['testingFacility']!=''){
               $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
           }
           if($parameters['locationOne']!=''){
               $sQuery = $sQuery->where(array('p.province_id'=>$parameters['locationOne']));
               if($parameters['locationTwo']!=''){
                     $sQuery = $sQuery->where(array('d.district_id'=>$parameters['locationTwo']));
               }
               if($parameters['locationThree']!=''){
                     $sQuery = $sQuery->where(array('c.city_id'=>$parameters['locationThree']));
               }
         }
               if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
                    $s_c_date = explode("to", $_POST['sampleTestedDates']);
                    if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                         $start_date = $general->dbDateFormat(trim($s_c_date[0]));
                    }
                    if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                         $end_date = $general->dbDateFormat(trim($s_c_date[1]));
                    }
               }

               if($parameters['sampleTestedDates']!=''){
                    $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date ."'", "r.sample_collection_date <='" . $end_date."'"));
               }
               if($parameters['tOutcome']!=''){
                    $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
                }
             
                if($parameters['finalOutcome']!=''){
                    $sQuery->where(array('final_outcome'=>$parameters['finalOutcome']));
                }

              $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
              $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
              return $rResult;
          }

          
          public function fetchFinalOutcomeChart($parameters)
          {
              $dbAdapter = $this->adapter;
              $sql = new Sql($dbAdapter);
              $general = new CommonService();
              $sQuery =   $sql->select()->from(array('r' => 'recency'))
              ->columns(
               array(
               "monthyear" => new Expression("DATE_FORMAT(hiv_recency_date, '%b %y')"),
               "ritaRecent" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'RITA Recent') THEN 1 ELSE 0 END))"),
               "longTerm" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Long Term') THEN 1 ELSE 0 END))"),
               "inconclusive" => new Expression("SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END)"),
               )
                    )
               ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'),'left')
               ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
               ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'),'left')
               ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'),'left')
               ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'),'left')
               ->group(array(new Expression('YEAR(hiv_recency_date)'),new Expression('MONTH(hiv_recency_date)')));
                    
               if($parameters['fName']!=''){
                    $sQuery->where(array('r.facility_id'=>$parameters['fName']));
                }
                if($parameters['testingFacility']!=''){
                    $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
                }
                if($parameters['locationOne']!=''){
                    $sQuery = $sQuery->where(array('p.province_id'=>$parameters['locationOne']));
                    if($parameters['locationTwo']!=''){
                          $sQuery = $sQuery->where(array('d.district_id'=>$parameters['locationTwo']));
                    }
                    if($parameters['locationThree']!=''){
                          $sQuery = $sQuery->where(array('c.city_id'=>$parameters['locationThree']));
                    }
              }
                    if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
                         $s_c_date = explode("to", $_POST['sampleTestedDates']);
                         if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                              $start_date = $general->dbDateFormat(trim($s_c_date[0]));
                         }
                         if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                              $end_date = $general->dbDateFormat(trim($s_c_date[1]));
                         }
                    }
     
                    if($parameters['sampleTestedDates']!=''){
                         $sQuery = $sQuery->where(array("r.hiv_recency_date >='" . $start_date ."'", "r.hiv_recency_date <='" . $end_date."'"));
                    }
                    if($parameters['tOutcome']!=''){
                         $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
                     }
                  
                     if($parameters['finalOutcome']!=''){
                         $sQuery->where(array('final_outcome'=>$parameters['finalOutcome']));
                     }
              $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               //\Zend\Debug\Debug::dump($sQueryStr);
              $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
              $j=0;
                foreach($rResult as $sRow){
                    if($sRow["monthyear"] == null) continue;
                    $result['finalOutCome']['RITA Recent'][$j] = (isset($sRow['ritaRecent']) && $sRow['ritaRecent'] != NULL) ? $sRow['ritaRecent'] : 0;
                    $result['finalOutCome']['Long Term'][$j] = (isset($sRow['longTerm']) && $sRow['longTerm'] != NULL) ? $sRow['longTerm'] : 0;
                    $result['finalOutCome']['Inconclusive'][$j] = (isset($sRow['inconclusive']) && $sRow['inconclusive'] != NULL) ? $sRow['inconclusive'] : 0;

                    $result['date'][$j] = $sRow["monthyear"];
                    $j++;
                }
              
              return $result;

          }
  
     }


     ?>