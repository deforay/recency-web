<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
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
        $common = new CommonService();
        $aColumns = array('r.sample_id','r.patient_id','r.facility_name','r.hiv_diagnosis_date','r.hiv_recency_date','r.hiv_recency_result','r.added_on','r.added_by');
        $orderColumns = array('r.sample_id','r.patient_id','r.facility_name','r.hiv_diagnosis_date','r.hiv_recency_date','r.hiv_recency_result','r.added_on','r.added_by');

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
                                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'));

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
              $row[] = $aRow['sample_id'];
              $row[] = $aRow['patient_id'];
              $row[] = ucwords($aRow['facility_name']);
              $row[] = $aRow['hiv_diagnosis_date'];
              $row[] = $aRow['hiv_recency_date'];
              $row[] = $aRow['hiv_recency_result'];
              $row[] = '<a href="/recency/edit/' . base64_encode($aRow['recency_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
              $output['aaData'][] = $row;
          }

          return $output;
      }

    public function addRecencyDetails($params)
    {
        $logincontainer = new Container('credo');
        $common = new CommonService();
        if(isset($params['sampleId']) && trim($params['sampleId'])!="")
        {
            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'hiv_diagnosis_date' => $common->dbDateFormat($params['hivDiagnosisDate']),
                'hiv_recency_date' => $common->dbDateFormat($params['hivRecencyDate']),
                'hiv_recency_result' => $params['hivRecencyResult'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId

            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchRecencyDetailsById($facilityId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('recency')
                                ->where(array('recency_id' => $facilityId));
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
                'hiv_recency_result' => $params['hivRecencyResult'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId
                
            );
            $updateResult = $this->update($data,array('recency_id'=>base64_decode($params['recencyId'])));
        }
        return $updateResult;
    }
}
?>
