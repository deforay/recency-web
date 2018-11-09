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
          $role = $sessionLogin->roleId;
          $roleCode = $sessionLogin->roleCode;
          $common = new CommonService();
          $aColumns = array('qc.qc_sample_id','qc.qc_test_date','qc.reference_result','qc.kit_lot_no','qc.tester_name');
          $orderColumns = array('qc.qc_sample_id','qc.qc_test_date','qc.reference_result','qc.kit_lot_no','qc.tester_name');

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

                    $sQuery = $sql->select()->from(array( 'qc' => 'quality_check_test'));

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
                         $sQuery = $sQuery->where('qc.added_by='.$sessionLogin->userId);
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
                               'hiv_recency_date' => (isset($params['hivRecencyDate']) && $params['hivRecencyDate']!='')?$common->dbDateFormat($params['hivRecencyDate']):NULL,
                               'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                               'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                               'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                               'term_outcome'=>$params['outcomeData'],
                               'tester_name' => $params['testerName'],
                               'comment' => $params['comment'],
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
                              'hiv_recency_date' => (isset($params['hivRecencyDate']) && $params['hivRecencyDate']!='')?$common->dbDateFormat($params['hivRecencyDate']):NULL,
                              'control_line' => (isset($params['controlLine']) && $params['controlLine']!='')?$params['controlLine']:NULL,
                              'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine']!='')?$params['positiveVerificationLine']:NULL,
                              'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine']!='')?$params['longTermVerificationLine']:NULL,
                              'term_outcome'=>$params['outcomeData'],
                              'tester_name' => $params['testerName'],
                              'comment' => $params['comment'],
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
                 $riskPopulationDb = new RiskPopulationsTable($this->adapter);
                 $common = new CommonService();

                 if(isset($params["qc"])){
                      $i = 1;
                      foreach($params["qc"] as $key => $qcTest){
                           try{

                                $userId = $qcTest['userId'];
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
                                    'hiv_recency_date' => (isset($qcTest['hivRecencyDate']) && $qcTest['hivRecencyDate']!='')?$common->dbDateFormat($qcTest['hivRecencyDate']):NULL,
                                    'control_line' => (isset($qcTest['ctrlLine']) && $qcTest['ctrlLine']!='')?$qcTest['ctrlLine']:NULL,
                                    'positive_verification_line' => (isset($qcTest['positiveLine']) && $qcTest['positiveLine']!='')?$qcTest['positiveLine']:NULL,
                                    'long_term_verification_line' => (isset($qcTest['longTermLine']) && $qcTest['longTermLine']!='')?$qcTest['longTermLine']:NULL,
                                    'tester_name' => $qcTest['testerName'],
                                    'added_on' => date("Y-m-d H:i:s"),
                                    'added_by' => $qcTest['userId'],

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
            $response['syncCount']['response'] = $this->getTotalSyncCount($userId);

            return $response;
      }

      public function getTotalSyncCount($userId)
      {
           $dbAdapter = $this->adapter;
           $sql = new Sql($dbAdapter);
           $query = $sql->select()->from(array('qc'=>'quality_check_test'))
           ->columns(array("Total" => new Expression('COUNT(*)'),))
           ->where(array('added_by'=>$userId));
           $queryStr = $sql->getSqlStringForSqlObject($query);
           $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
           // \Zend\Debug\Debug::dump($result);die;
           return $result;
      }
}
?>
