<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
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

                    $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                    //   echo $sQueryStr;die;
                    $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

                    /* Data set length after filtering */

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
                         $row[] = ucwords($aRow['qc_sample_id']);
                         $row[] = $common->humanDateFormat($aRow['qc_test_date']);
                         $row[] = ucwords($aRow['reference_result']);
                         $row[] = ucwords($aRow['kit_lot_no']);
                         $row[] = ucwords($aRow['tester_name']);
                         $row[] = '<a href="/quality-check/edit/' . base64_encode($aRow['qc_test_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
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
                     if( (isset($params['qcSampleId']) && trim($params['qcSampleId'])!="") || (isset($params['testKitLotNo']) && trim($params['testKitLotNo'])!="") )
                     {
                          // \Zend\Debug\Debug::dump($params);die;
                          $data = array(
                               'qc_sample_id' => $params['qcSampleId'],
                               'qc_test_date'=>($params['qcTestDate']!='')?$common->dbDateFormat($params['qcTestDate']):NULL,
                               'reference_result' => $params['referenceResult'],
                               'kit_lot_no'=>$params['testKitLotNo'],
                               'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                               'recency_test_performed'=>$params['recencyTestPerformed'],
                               'recency_test_not_performed_reason'=> $params['recencyTestNotPerformedReason'],
                               'other_recency_test_not_performed_reason'=> $params['otherRecencyTestNotPerformedReason'],
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
                     }
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
                         \Zend\Debug\Debug::dump($params['qualityCheckId']);
                         $data = array(
                              'qc_sample_id' => $params['qcSampleId'],
                              'qc_test_date'=>($params['qcTestDate']!='')?$common->dbDateFormat($params['qcTestDate']):NULL,
                              'reference_result' => $params['referenceResult'],
                              'kit_lot_no'=>$params['testKitLotNo'],
                              'kit_expiry_date' => ($params['testKitExpDate']!='')?$common->dbDateFormat($params['testKitExpDate']):NULL,
                              'recency_test_performed'=>$params['recencyTestPerformed'],
                              'recency_test_not_performed_reason'=> $params['recencyTestNotPerformedReason'],
                              'other_recency_test_not_performed_reason'=> $params['otherRecencyTestNotPerformedReason'],
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
                         \Zend\Debug\Debug::dump($data);die;

                         $updateResult = $this->update($data,array('qc_test_id'=>$params['qualityCheckId']));
                    }
                    return $updateResult;
               }

               public function fetchFacilitiesAllDetails()
               {
                  $dbAdapter = $this->adapter;
                  $sql = new Sql($dbAdapter);
                  $logincontainer = new Container('credo');
                  $riskPopulationsDb = new \Application\Model\RiskPopulationsTable($this->adapter);
                  if($logincontainer->roleCode=='user'){
                        $sQuery = $sql->select()->from(array( 'ufm' => 'user_facility_map' ))
                                    ->join(array('f' => 'facilities'), 'f.facility_id = ufm.facility_id', array('facility_name','facility_id'))
                                    ->where(array('f.status'=>'active','ufm.user_id'=>$logincontainer->userId));
                         $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                         $result['facility'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                  }else{
                        $result['facility'] = $this->select()->toArray();
                  }
                  $result['riskPopulations'] = $riskPopulationsDb->select()->toArray();
                  return $result;
               }

               public function fetchFacilitiesDetailsApi($params)
               {
                    $dbAdapter = $this->adapter;
                    $sql = new Sql($dbAdapter);
                    if($params['userId']!=''){
                         $sQuery = $sql->select()->from(array( 'f' => 'facilities' ))
                         ->join(array('r' => 'recency'), 'f.facility_id = r.facility_id', array('sample_id'))
                         ->where(array('f.status'=>'active','r.added_by'=>$params['userId']));
                         $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                         $fResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

                         if(count($fResult)>0){
                              return $fResult;
                         }
                    }else{
                         $sQuery = $sql->select()->from(array('f'=>'facilities'))
                         ->where(array('status'=>'active'));
                         $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                         $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                         return $rResult;
                    }
               }
               public function fetchFacilityByLocation($params)
               {
                  $dbAdapter = $this->adapter;
                  $sql = new Sql($dbAdapter);
                  $sQuery = $sql->select()->from(array( 'f' => 'facilities'))->columns(array('facility_id','facility_name'));
                  if($params['locationOne']!=''){
                        $sQuery = $sQuery->where(array('province'=>$params['locationOne']));
                        if($params['locationTwo']!=''){
                              $sQuery = $sQuery->where(array('district'=>$params['locationTwo']));
                        }
                        if($params['locationThree']!=''){
                              $sQuery = $sQuery->where(array('city'=>$params['locationThree']));
                        }
                  }
                  if(isset($params['facilityId']) && $params['facilityId']!=NULL){
                        $fDeocde = json_decode($params['facilityId']);
                        if(!empty($fDeocde)){
                              $sQuery = $sQuery->where('facility_id NOT IN('.implode(",",$fDeocde).')');
                        }
                  }
                  $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
                  $fResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
                  return $fResult;
               }

               public function checkFacilityName($fName)
               {
                  $dbAdapter = $this->adapter;
                  $sql = new Sql($dbAdapter);
                  $fQuery = $sql->select()->from('facilities')->columns(array('facility_id','facility_name'))
                                    ->where(array('facility_name' => trim($fName)));
                  $fQueryStr = $sql->getSqlStringForSqlObject($fQuery); // Get the string of the Sql, instead of the Select-instance
                  $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                  return $fResult;
               }
          }
          ?>
