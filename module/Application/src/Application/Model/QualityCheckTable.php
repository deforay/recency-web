<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

class QualityCheckTable extends AbstractTableGateway {

     protected $table = 'quality_check_test';

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }

     public function fetchQualityCheckDetails($parameters) {

          /* Array of database columns which should be read and sent back to DataTables. Use a space where
          * you want to insert a non-database field (for example a counter or static image)
          */
          $sessionLogin = new Container('credo');
          $queryContainer = new Container('query');
          $role = $sessionLogin->roleId;
          $roleCode = $sessionLogin->roleCode;
          $common = new CommonService();
          $aColumns = array('qc.qc_sample_id','qc.qc_test_date','qc.reference_result','qc.kit_lot_no','DATE_FORMAT(qc.kit_expiry_date,"%d-%b-%Y")','DATE_FORMAT(qc.hiv_recency_test_date,"%d-%b-%Y")','qc.control_line','qc.positive_verification_line','qc.long_term_verification_line','qc.term_outcome','qc.tester_name');
          $orderColumns = array('qc.qc_sample_id','qc.qc_test_date','qc.reference_result','qc.kit_lot_no','qc.kit_expiry_date','qc.hiv_recency_test_date','qc.control_line','qc.positive_verification_line','qc.long_term_verification_line','qc.term_outcome','qc.tester_name');

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

                    $sQuery = $sql->select()->from(array( 'qc' => 'quality_check_test'));

                    if (isset($sWhere) && $sWhere != "") {
                         $sQuery->where($sWhere);
                    }

                        // \Zend\Debug\Debug::dump($parameters);die;

                    if($parameters['tOutcome']!=''){
                        $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
                    }

                    if (isset($sOrder) && $sOrder != "") {
                         $sQuery->order($sOrder);
                    }

                    if (isset($sLimit) && isset($sOffset)) {
                         $sQuery->limit($sLimit);
                         $sQuery->offset($sOffset);
                    }

                    if($roleCode=='user'){
                         $sQuery = $sQuery->where('qc.added_by='.$sessionLogin->userId);
                    }
                    $queryContainer->exportQcDataQuery = $sQuery;
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
                    $iQuery = $sql->select()->from(array( 'qc' => 'quality_check_test' ));

                    if($roleCode=='user'){
                         $iQuery = $iQuery->where('qc.added_by='.$sessionLogin->userId);
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
                         $row[] = ucwords($aRow['qc_sample_id']);
                         $row[] = $common->humanDateFormat($aRow['qc_test_date']);
                         $row[] = str_replace("_"," ",ucwords($aRow['reference_result']));
                         $row[] = ucwords($aRow['kit_lot_no']);
                         $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);
                         $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
                         $row[] = ucwords($aRow['control_line']);
                         $row[] = ucwords($aRow['positive_verification_line']);
                         $row[] = ucwords($aRow['long_term_verification_line']);
                         $row[] = ucwords($aRow['term_outcome']);
                         $row[] = ucwords($aRow['tester_name']);

                         // $row[] = '<a href="/quality-check/edit/' . base64_encode($aRow['qc_test_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';

                         $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                         <a class="btn btn-danger" href="/quality-check/edit/' . base64_encode($aRow['qc_test_id']) . '"><i class="si si-pencil"></i> Edit</a>
                         <a class="btn btn-primary" href="/quality-check/view/' . base64_encode($aRow['qc_test_id']) . '"><i class="si si-eye"></i> View</a>

                         </div>';

                         $output['aaData'][] = $row;
                    }

                    return $output;
               }

               public function addQualityCheckTestResultDetails($params)
               {
                    //\Zend\Debug\Debug::dump($params);die;
                     $dbAdapter = $this->adapter;
                     $sql = new Sql($dbAdapter);
                     $logincontainer = new Container('credo');
                     $common = new CommonService();

                          $data = array(
                               'qc_sample_id' => $params['qcSampleId'],
                               'qc_test_date'=>($params['qcTestDate']!='')?$common->dbDateFormat($params['qcTestDate']):NULL,
                               'reference_result' => $params['referenceResult'],
                               //'kit_name'=>$params['testKitName'],
                               'kit_lot_no'=>$params['testKitLotNo'],
                               'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                               // 'recency_test_performed'=>$params['recencyTestPerformed'],
                               // 'recency_test_not_performed_reason'=> $params['recencyTestNotPerformedReason'],
                               // 'other_recency_test_not_performed_reason'=> $params['otherRecencyTestNotPerformedReason'],
                               'hiv_recency_test_date' => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate']!='')?$common->dbDateFormat($params['hivRecencyTestDate']):NULL,
                               'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                               'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                               'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                               'term_outcome'=>$params['outcomeData'],
                               'tester_name' => $params['testerName'],
                               'comment' => $params['comment'],
                               'testing_facility_id' => $params['testingFacilityId'],
                               'final_result' => $params['finalResult'],
                               'added_on' => date("Y-m-d H:i:s"),
                               'added_by' => $logincontainer->userId,

                          );

                          $this->insert($data);
                          $lastInsertedId = $this->lastInsertValue;

                     return $lastInsertedId;
               }

               public function fetchQualityCheckTestDetailsById($qualityCheckId)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);

                    $sQuery = $sql->select()->from('quality_check_test')
                                   ->where(array('qc_test_id' => $qualityCheckId));

                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    return $rResult;

               }

               public function updateQualityCheckTestDetails($params)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    $logincontainer = new Container('credo');
                    $common = new CommonService();

                    if(isset($params['qualityCheckId']) && trim($params['qualityCheckId'])!="")
                    {
                         $data = array(
                              'qc_sample_id' => $params['qcSampleId'],
                              'qc_test_date'=>($params['qcTestDate']!='')?$common->dbDateFormat($params['qcTestDate']):NULL,
                              'reference_result' => $params['referenceResult'],
                              'kit_lot_no'=>$params['testKitLotNo'],
                              //'kit_name'=>$params['testKitName'],
                              'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                              // 'recency_test_performed'=>$params['recencyTestPerformed'],
                              // 'recency_test_not_performed_reason'=> $params['recencyTestNotPerformedReason'],
                              // 'other_recency_test_not_performed_reason'=> $params['otherRecencyTestNotPerformedReason'],
                              'hiv_recency_test_date' => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate']!='')?$common->dbDateFormat($params['hivRecencyTestDate']):NULL,
                              'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                              'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                              'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                              'term_outcome'=>$params['outcomeData'],
                              'tester_name' => $params['testerName'],
                              'comment' => $params['comment'],
                              'testing_facility_id' => $params['testingFacilityId'],
                              'final_result' => $params['finalResult'],
                              //'added_on' => date("Y-m-d H:i:s"),
                              'added_by' => $logincontainer->userId,

                         );

                         $updateResult = $this->update($data,array('qc_test_id'=>$params['qualityCheckId']));
                    }
                    return $updateResult;
               }

          public function fetchQcDetails($id)
          {
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('qc' => 'quality_check_test'))
               ->where(array('qc_test_id' =>$id));
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
               return $rResult;
          }


      public function addQualityCheckDetailsApi($params)
      {
                 $dbAdapter = $this->adapter;
                 $sql = new Sql($dbAdapter);
                 $logincontainer = new Container('credo');
                 $facilityDb = new FacilitiesTable($this->adapter);
                 $globalDb = new GlobalConfigTable($this->adapter);
                 $riskPopulationDb = new RiskPopulationsTable($this->adapter);
                 $globalDb = new GlobalConfigTable($this->adapter);
                 $common = new CommonService();

                 if(isset($params["qc"])){
                        //check user status active or not
                        $uQuery = $sql->select()->from('users')
                                        ->where(array('user_id' => $params["qc"][0]['syncedBy']));
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
                      foreach($params["qc"] as $key => $qcTest){
                           try{

                                $syncedBy = $qcTest['syncedBy'];
                                $data = array(
                                    'qc_sample_id' => $qcTest['qcsampleId'],
                                    'qc_test_date'=>($qcTest['qcTestDate']!='')?$common->dbDateFormat($qcTest['qcTestDate']):NULL,
                                    'reference_result' => $qcTest['referenceResult'],
                                    'kit_lot_no'=>$qcTest['testKitLotNo'],
                                    // 'kit_name'=>$qcTest['testKitName'],
                                    'kit_expiry_date' => ($qcTest['testKitExpDate']!='')?$common->dbDateFormat($qcTest['testKitExpDate']):NULL,
                                    //'recency_test_performed'=>$qcTest['recencyTestPerformed'],
                                    //'recency_test_not_performed_reason'=> $qcTest['recencyTestNotPerformedReason'],
                                    //'other_recency_test_not_performed_reason'=> $qcTest['otherRecencyTestNotPerformedReason'],
                                    'hiv_recency_test_date' => (isset($qcTest['hivRecencyTestDate']) && $qcTest['hivRecencyTestDate']!='')?$common->dbDateFormat($qcTest['hivRecencyTestDate']):NULL,
                                    'control_line' => (isset($qcTest['ctrlLine']) && $qcTest['ctrlLine']!='')?$qcTest['ctrlLine']:NULL,
                                    'positive_verification_line' => (isset($qcTest['positiveLine']) && $qcTest['positiveLine']!='')?$qcTest['positiveLine']:NULL,
                                    'long_term_verification_line' => (isset($qcTest['longTermLine']) && $qcTest['longTermLine']!='')?$qcTest['longTermLine']:NULL,
                                    'term_outcome'=>$qcTest['recencyOutcome'],
                                    'tester_name' => $qcTest['testerName'],
                                    'app_version'=>$qcTest['appVersion'],
                                    'added_on' => date('Y-m-d H:i:s'),
                                    'added_by' => $qcTest['addedBy'],
                                    'sync_by' => $qcTest['syncedBy'],
                                    'form_initiation_datetime'=>$qcTest['formInitDateTime'],
                                    'form_transfer_datetime'=>$qcTest['formTransferDateTime'],
                                    'form_saved_datetime'=>$qcTest['formSavedDateTime'],
                                    'testing_facility_id'=>$qcTest['testingFacility'],
                                    'unique_id'=>isset($qcTest['unique_id'])?$qcTest['unique_id']:NULL,
                                );

                                $this->insert($data);
                                $lastInsertedId = $this->lastInsertValue;
                                if($lastInsertedId > 0){
                                    $response['syncData']['response'][$key] = 'success';
                                }else{
                                    $response['syncData']['response'][$key] = 'failed';
                                }
                      }
                      catch (Exception $exc) {
                           error_log($exc->getMessage());
                           error_log($exc->getTraceAsString());
                      }
                      $i++;
                }
            }
            $response['syncCount']['response'] = $this->getTotalSyncCount($syncedBy);
            $response['syncCount']['tenRecord'] = $this->getQCSyncData($syncedBy);
            return $response;
      }

      public function getTotalSyncCount($syncedBy)
      {
           $dbAdapter = $this->adapter;
           $sql = new Sql($dbAdapter);
           $query = $sql->select()->from(array('qc'=>'quality_check_test'))
           ->columns(array("Total" => new Expression('COUNT(*)'),))
           ->where(array('sync_by'=>$syncedBy));
           $queryStr = $sql->getSqlStringForSqlObject($query);
           $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
           //\Zend\Debug\Debug::dump($result);die;
           return $result;
      }

     public function getQCSyncData($syncedBy)
     {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('qc'=>'quality_check_test'))
                    ->where(array('sync_by'=>$syncedBy))
                    ->order("qc.qc_test_id DESC")
                    ->limit(10);
        $queryStr = $sql->getSqlStringForSqlObject($query);
        $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $result;
     }

     public function fetchQualityCheckVolumeChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $sQuery =   $sql->select()->from(array('qc' => 'quality_check_test'))
               ->columns(array('tester_name',"total" => new Expression('COUNT(*)')))
               ->group('tester_name');
          
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc.qc_test_date >='".$start_date."' AND qc.qc_test_date<='".$end_date."')");
          }
          
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          //\Zend\Debug\Debug::dump($sQueryStr);die;
          $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
          foreach($rResult as $sRow){
               if($sRow["tester_name"] == null) continue;
               $result[$sRow['tester_name']] = (isset($sRow['total']) && $sRow['total'] != NULL) ? $sRow['total'] : 0;
          }
          return $result;
     }
     
     public function fetchQualityResultTermOutcomeChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
          if ($format == 'percentage') {
               $sQuery = $sql->select()->from('quality_check_test')
               ->columns(array(
                    "totalSamples" => new Expression('COUNT(*)'),
                    "negative" => new Expression("(SUM(CASE
                                             WHEN (term_outcome ='Assay HIV Negative') THEN 1
                                             ELSE 0
                                             END) / COUNT(*)) * 100"),
                    "lt" => new Expression("(SUM(CASE
                                             WHEN (term_outcome ='Long Term') THEN 1
                                             ELSE 0
                                             END) / COUNT(*)) * 100"),
                    "r" => new Expression("(SUM(CASE
                                             WHEN (term_outcome ='Assay Recent') THEN 1
                                             ELSE 0
                                             END) / COUNT(*)) * 100")
               ));
          }else{
               $sQuery = $sql->select()->from('quality_check_test')
               ->columns(array(
                    "totalSamples" => new Expression('COUNT(*)'),
                    "negative" => new Expression("SUM(CASE
                                             WHEN (term_outcome ='Assay HIV Negative') THEN 1
                                             ELSE 0
                                             END)"),
                    "lt" => new Expression("SUM(CASE
                                             WHEN (term_outcome ='Long Term') THEN 1
                                             ELSE 0
                                             END)"),
                    "r" => new Expression("SUM(CASE
                                             WHEN (term_outcome ='Assay Recent') THEN 1
                                             ELSE 0
                                             END)")
               ));
          }

          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc_test_date >='".$start_date."' AND qc_test_date<='".$end_date."')");
          }
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          // echo $sQueryStr;die;
          $rResult['result'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          $rResult['format'] = $format;
          return $rResult;
     }
     
     public function fetchKitLotNumberChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
          $sQuery = $sql->select()->from('quality_check_test')
          ->columns(array('kit_lot_no',"total" => new Expression('COUNT(*)')))
          ->group('kit_lot_no')
          ->where("kit_lot_no != 'NULL'");

          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc_test_date >='".$start_date."' AND qc_test_date<='".$end_date."')");
          }
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          // echo $sQueryStr;die;
          $result = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
          
          // To get total count from the list
          foreach($result as $count){
               $total[] = $count['total'];
          }
          $totalVal = array_sum($total);
          // To get format list
          if($format == "percentage"){
               foreach($result as $count){
                    $totalResult[$count['kit_lot_no']] = number_format((($count['total']/$totalVal)*100),2);
               }
          }else{
               foreach($result as $count){
                    $totalResult[$count['kit_lot_no']] = $count['total'];
               }
          }
          $rResult['result'] = $totalResult;
          $rResult['total'] = $totalVal;
          $rResult['format'] = $format;
          return $rResult;
     }
     
     public function fetchSampleLotChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
          $sQuery = $sql->select()->from('quality_check_test')
               ->columns(array('qc_sample_id',"total" => new Expression('COUNT(*)')))
               ->group('qc_sample_id')
               ->where("qc_sample_id != 'NULL'");
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc_test_date >='".$start_date."' AND qc_test_date<='".$end_date."')");
          }
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          // echo $sQueryStr;die;
          $result = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
          
          // To get total count from the list
          foreach($result as $count){
               $total[] = $count['total'];
          }
          $totalVal = array_sum($total);
          // To get format list
          if($format == "percentage"){
               foreach($result as $count){
                    $totalResult[$count['qc_sample_id']] = number_format((($count['total']/$totalVal)*100),2);
               }
          }else{
               foreach($result as $count){
                    $totalResult[$count['qc_sample_id']] = $count['total'];
               }
          }
          $rResult['result'] = $totalResult;
          $rResult['total'] = $totalVal;
          $rResult['format'] = $format;
          //\Zend\Debug\Debug::dump($rResult);die;
          return $rResult;
     }
     
     public function fetchTestingQualityNegativeChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
          if ($format == 'percentage') {
               $sQuery = $sql->select()->from('quality_check_test')
                    ->columns(array(
                         "totalSamples" => new Expression('COUNT(*)'),
                         "negative" => new Expression("(SUM(CASE
                                                  WHEN (term_outcome ='Assay HIV Negative') THEN 1
                                                  ELSE 0
                                                  END) / COUNT(*)) * 100")
                    ));
          }else{
               $sQuery = $sql->select()->from('quality_check_test')
                    ->columns(array('term_outcome',"totalSamples" => new Expression('COUNT(*)'),"negative" => new Expression('COUNT(*)')))
                    ->where(array('term_outcome'=>'Assay HIV Negative'));
          }
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc_test_date >='".$start_date."' AND qc_test_date<='".$end_date."')");
          }
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          // echo $sQueryStr;die;
          $rResult['result'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          $rResult['format'] = $format;
          
          $tQuery = $sql->select()->from('quality_check_test')
                    ->columns(array(
                         "total" => new Expression("SUM(CASE
                                                  WHEN (term_outcome ='Assay HIV Negative') THEN 1
                                                  ELSE 0
                                                  END)")
                    ));
          $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
          $total = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          $rResult['result']['total'] = $total['total'];
          return $rResult;
     }
     
     public function fetchTestingQualityInvalidChart($parameters)
     {
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
          if ($format == 'percentage') {
               $sQuery = $sql->select()->from('quality_check_test')
                    ->columns(array(
                         "totalSamples" => new Expression('COUNT(*)'),
                         "invalid" => new Expression("(SUM(CASE
                                                  WHEN (term_outcome ='Invalid – Please Verify') THEN 1
                                                  ELSE 0
                                                  END) / COUNT(*)) * 100")
                    ));
          }else{
               $sQuery = $sql->select()->from('quality_check_test')
                    ->columns(array('term_outcome',"totalSamples" => new Expression('COUNT(*)'),"invalid" => new Expression('COUNT(*)')))
                    ->where(array('term_outcome'=>'Invalid – Please Verify'));
          }
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if($parameters['sampleTestedDates']!=''){
               $sQuery = $sQuery->where("(qc_test_date >='".$start_date."' AND qc_test_date<='".$end_date."')");
          }
          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          // echo $sQueryStr;die;
          $rResult['result'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          $rResult['format'] = $format;

          $tQuery = $sql->select()->from('quality_check_test')
                    ->columns(array(
                         "total" => new Expression("SUM(CASE
                                                  WHEN (term_outcome ='Invalid – Please Verify') THEN 1
                                                  ELSE 0
                                                  END)")
                    ));
          $tQueryStr = $sql->getSqlStringForSqlObject($tQuery);
          $total = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
          $rResult['result']['total'] = $total['total'];
          return $rResult;
     }

     public function fetchPassedQualityBasedOnFacility($parameters) {

          /* Array of database columns which should be read and sent back to DataTables. Use a space where
          * you want to insert a non-database field (for example a counter or static image)
          */
          $sessionLogin = new Container('credo');
          $common = new CommonService();
          $aColumns = array('ft.facility_name','qc.kit_lot_no','d.district_name');
          $orderColumns = array('ft.facility_name','qc.kit_lot_no','d.district_name');

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

               $sQuery = $sql->select()->from(array( 'qc' => 'quality_check_test'))
                         ->columns(
                              array(
                                   'kit_lot_no',
                                   "total" => new Expression('COUNT(*)'),
                                   "pass" => new Expression("(SUM(CASE WHEN (qc.final_result = 'pass') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                                   "fail" => new Expression("(SUM(CASE WHEN (qc.final_result = 'fail' or final_result is NULL or final_result='') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                              )
                         );                    
               
               $sQuery = $sQuery
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('facility_name'))
                    ->join(array('d' => 'district_details'), 'd.district_id = ft.district', array('district_name'), 'left')
                    //->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
                    ->group(new Expression("kit_lot_no,facility_name"));

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

               $queryContainer->exportQcDataQuery = $sQuery;
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               //echo $sQueryStr;die;
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

               /* Data set length after filtering */
               $sQuery->reset('limit');
               $sQuery->reset('offset');
               $tQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
               $iFilteredTotal = count($aResultFilterTotal);

               /* Total data set length */
               $iQuery = $sql->select()->from(array( 'qc' => 'quality_check_test' ))
                         ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('facility_name'))
                         ->group(new Expression("qc.kit_lot_no,ft.facility_name"));

               $iQueryStr = $sql->getSqlStringForSqlObject($iQuery);
               $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               $output = array(
                    "sEcho" => intval($parameters['sEcho']),
                    "iTotalRecords" => count($iResult),
                    "iTotalDisplayRecords" => $iFilteredTotal,
                    "aaData" => array()
               );

               foreach ($rResult as $aRow) {

                    $row = array();
                    $row[] = ucwords($aRow['facility_name']);
                    $row[] = ucwords($aRow['kit_lot_no']);
                    $row[] = ucwords($aRow['district_name']);
                    $row[] = round($aRow['pass'],2);
                    
                    $output['aaData'][] = $row;
               }
               return $output;
     }

     public function fetchMonthWiseQualityControlChart($parameters)
     {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('qc' => 'quality_check_test'));
          if ($format == 'percentage') {
               $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "monthyear" => new Expression("DATE_FORMAT(qc.qc_test_date, '%b-%Y')"),
                        "pass" => new Expression("(SUM(CASE WHEN (qc.final_result = 'pass') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "fail" => new Expression("(SUM(CASE WHEN (qc.final_result = 'fail' or final_result is NULL or final_result='') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
          } else {
               $sQuery = $sQuery
                    ->columns(
                         array(
                         "total" => new Expression('COUNT(*)'),
                         "monthyear" => new Expression("DATE_FORMAT(qc.qc_test_date, '%b-%Y')"),
                         "pass" => new Expression("SUM(CASE WHEN (qc.final_result='pass') THEN 1 ELSE 0 END)"),
                         "fail" => new Expression("SUM(CASE WHEN ((qc.final_result='fail' or final_result is NULL or final_result='')) THEN 1 ELSE 0 END)"),
                         )
                    );
          }

          $sQuery=$sQuery
               ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('testing_facility_name' => 'facility_name'),'left')
               ->join(array('d' => 'district_details'), 'd.district_id = ft.district', array('district_name'), 'left')
               ->group(array(new Expression("DATE_FORMAT(qc.qc_test_date,'%b-%Y')")))
               ->order("qc.qc_test_date");

          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!=''){
               $sQuery = $sQuery->where("(qc.qc_test_date >='".$start_date."' AND qc.qc_test_date<='".$end_date."')");
          }

          if(isset($parameters['testingFacility']) && trim($parameters['testingFacility'])!=""){
               $sQuery = $sQuery->where(array('qc.testing_facility_id'=>$parameters['testingFacility']));
          }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format']= $format;
          foreach ($rResult as $sRow) {
               if ($sRow["monthyear"] == null) {
                    continue;
               }

               $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
               $result['pass'][$j] = (isset($sRow['pass']) && $sRow['pass'] != null) ? round($sRow['pass'], 2) : 0;
               $result['fail'][$j] = (isset($sRow["fail"]) && $sRow["fail"] != null) ? round($sRow["fail"], 2) : 0;

               $result['date'][$j] = ($sRow["monthyear"]) . " (N=$n)";
            
               $result['total'] += $n;

               $j++;
          }
          //\Zend\Debug\Debug::dump($result);die;
          return $result;
     }

     public function fetchDistrictWiseQualityCheckInvalid($parameters){
          $dbAdapter = $this->adapter;
          $sql = new Sql($dbAdapter);
          $general = new CommonService();
          $sQuery = $sql->select()->from(array('qc' => 'quality_check_test'))
                    ->columns(array(
                         "total" => new Expression('COUNT(*)'),
                         "negativePercentage" => new Expression("(SUM(CASE WHEN (term_outcome ='Assay HIV Negative') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                         "negativeCount" => new Expression("SUM(CASE WHEN (term_outcome='Assay HIV Negative') THEN 1 ELSE 0 END)"),
                         "invalidPercentage" => new Expression("(SUM(CASE WHEN (term_outcome = 'Invalid – Please Verify') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                         "invalidCount" => new Expression("SUM(CASE WHEN (term_outcome='Invalid – Please Verify') THEN 1 ELSE 0 END)")
                    ))
                    ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('facility_name'))
                    ->join(array('d' => 'district_details'), 'd.district_id = ft.district', array('district_name'), 'left')
                    ->group('district_name');

          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
               $s_c_date = explode("to", $parameters['sampleTestedDates']);
               if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $general->dbDateFormat(trim($s_c_date[0]));
               }
               if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $general->dbDateFormat(trim($s_c_date[1]));
               }
          }
          if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!=''){
               $sQuery = $sQuery->where("(qc.qc_test_date >='".$start_date."' AND qc.qc_test_date<='".$end_date."')");
          }

          if(isset($parameters['testingFacility']) && trim($parameters['testingFacility'])!=""){
               $sQuery = $sQuery->where(array('qc.testing_facility_id'=>$parameters['testingFacility']));
          }
          if(isset($parameters['locationThree']) && trim($parameters['locationThree'])!=""){
               $sQuery = $sQuery->where(array('d.district_id'=>$parameters['locationThree']));
          }

          $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
          return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
     }

     
     public function fetchQualityCheckReportDetails($parameters) {

          /* Array of database columns which should be read and sent back to DataTables. Use a space where
          * you want to insert a non-database field (for example a counter or static image)
          */
          $sessionLogin = new Container('credo');
          $common = new CommonService();
          $aColumns = array('qc.tester_name','total','ft.facility_name');
          $orderColumns = array('qc.tester_name','total','ft.facility_name');

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

               $sQuery = $sql->select()->from(array( 'qc' => 'quality_check_test'))
                         ->columns(
                              array(
                                   'tester_name',
                                   "total" => new Expression('COUNT(*)'),
                              )
                              )
                              ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('facility_name'))
                              ->group("tester_name");     
                              
                              
               if(isset($parameters['testDate']) && trim($parameters['testDate'])!= ''){
                    $s_c_date = explode("to", $parameters['testDate']);
                    if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                         $start_date = $general->dbDateFormat(trim($s_c_date[0]));
                    }
                    if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                         $end_date = $general->dbDateFormat(trim($s_c_date[1]));
                    }
               }
               if(isset($parameters['testDate']) && trim($parameters['testDate'])!=''){
                    $sQuery = $sQuery->where("(qc.qc_test_date >='".$start_date."' AND qc.qc_test_date<='".$end_date."')");
               }
     
               if(isset($parameters['testingFacility']) && trim($parameters['testingFacility'])!=""){
                    $sQuery = $sQuery->where(array('qc.testing_facility_id'=>$parameters['testingFacility']));
               }
             
               if(isset($parameters['qualityCheck']) && trim($parameters['qualityCheck'])!=""){
                    if($parameters['qualityCheck'] == 'qc_not_performed'){
                        $sQuery->where(array('recency_test_performed' => ''));
                    }else if($parameters['qualityCheck'] == 'qc_performed'){
                        $sQuery->where('recency_test_performed != ""');
                    }
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

               $queryContainer->exportQcDataQuery = $sQuery;
               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               //echo $sQueryStr;die;
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

               /* Data set length after filtering */
               $sQuery->reset('limit');
               $sQuery->reset('offset');
               $tQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
               $iFilteredTotal = count($aResultFilterTotal);

               /* Total data set length */
               $iQuery = $sql->select()->from(array( 'qc' => 'quality_check_test' ))
                         ->join(array('ft' => 'facilities'), 'ft.facility_id = qc.testing_facility_id', array('facility_name'))
                         ->group("tester_name");   

               $iQueryStr = $sql->getSqlStringForSqlObject($iQuery);
               $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               $output = array(
                    "sEcho" => intval($parameters['sEcho']),
                    "iTotalRecords" => count($iResult),
                    "iTotalDisplayRecords" => $iFilteredTotal,
                    "aaData" => array()
               );

               foreach ($rResult as $aRow) {

                    $row = array();
                    $row[] = ucwords($aRow['tester_name']);
                    $row[] = $aRow['total'];
                    $row[] = ucwords($aRow['facility_name']);
                    
                    $output['aaData'][] = $row;
               }
               return $output;
     }
}
?>
