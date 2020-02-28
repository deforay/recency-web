<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;
use Zend\Config\Writer\PhpArray;

class RoleTable extends AbstractTableGateway {

    protected $table = 'roles';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchRoleAllDetails(){
        return $this->select("role_status='active'")->toArray();
    }

    public function fetchUserDetails($parameters) {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('u.user_name','r.role_name','u.email','u.district','u.app_password','u.server_password','u.alt_email','u.mobile','u.alt_mobile','u.job_responsibility','u.comments','u.status');
        $orderColumns = array('u.user_name','r.role_name','u.email','u.district','u.app_password','u.server_password','u.alt_email','u.mobile','u.alt_mobile','u.job_responsibility','u.comments','u.status');

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

          $sQuery = $sql->select()->from(array( 'u' => 'users' ))
                                ->join(array('r' => 'roles'), 'u.role_id = r.role_id', array('role_name'));

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

          $output = array(
                  "sEcho" => intval($parameters['sEcho']),
                  "iTotalRecords" => count($tResult),
                  "iTotalDisplayRecords" => $iFilteredTotal,
                  "aaData" => array()
          );

          foreach ($rResult as $aRow) {

              $row = array();
              $row[] = ucwords($aRow['user_name']);
              $row[] = ucwords($aRow['role_name']);
              $row[] = $aRow['email'];
              $row[] = $aRow['alt_email'];
              $row[] = $aRow['mobile'];
              $row[] = $aRow['alt_mobile'];
              $row[] = $aRow['job_responsibility'];
              $row[] = $aRow['comments'];
              $row[] = ucwords($aRow['status']);
              $row[] = '<a href="/user/edit/' . base64_encode($aRow['user_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
              $output['aaData'][] = $row;
          }

          return $output;
      }

    public function addUserDetails($params)
    {
        if(isset($params['facilityName']) && trim($params['facilityName'])!="")
        {
            $data = array(
                'facility_name' => $params['facilityName'],
                'province' => $params['province'],
                'district' => $params['district'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'status' => $params['facilityStatus']

            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchUserDetailsById($userId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('users')
                                ->where(array('user_id' => $userId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function updateUserDetails($params)
    {
        if(isset($params['facilityName']) && trim($params['facilityName'])!="")
        {
            $data = array(
                'facility_name' => $params['facilityName'],
                'province' => $params['province'],
                'district' => $params['district'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'status' => $params['facilityStatus']

            );
            $updateResult = $this->update($data,array('facility_id'=>base64_decode($params['facilityId'])));
        }
        return $updateResult;
    }

}
?>
