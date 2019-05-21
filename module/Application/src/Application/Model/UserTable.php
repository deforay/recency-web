<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class UserTable extends AbstractTableGateway {

    protected $table = 'users';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function loginProcessDetails($params){
		$alertContainer = new Container('alert');
        $logincontainer = new Container('credo');
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(isset($params['userName']) && trim($params['userName'])!="" && trim($params['loginPassword'])!=""){
            $password = sha1($params['loginPassword'] . $configResult["password"]["salt"]);
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $globalDb = new \Application\Model\GlobalConfigTable($this->adapter);
            $sQuery = $sql->select()->from(array('u' => 'users'))
                    ->join(array('r' => 'roles'), 'u.role_id = r.role_id', array('role_code'))
				    ->where(array('u.email' => $params['userName'], 'u.server_password' => $password,'u.web_access'=>'yes' ));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

            if($rResult) {
                if($rResult->status=='inactive'){
                    $adminEmail = $globalDb->getGlobalValue('admin_email');
                    $adminPhone = $globalDb->getGlobalValue('admin_phone');

                    $alertContainer->alertMsg = 'Your password has expired or has been locked, please contact your administrator('.$adminEmail.' or '.$adminPhone.')';
                return 'login';
                }
                $logincontainer->userId = $rResult->user_id;
                $logincontainer->roleId = $rResult->role_id;
                $logincontainer->roleCode = $rResult->role_code;
                $logincontainer->userName = ucwords($rResult->user_name);
                $logincontainer->userEmail = ucwords($rResult->email);
                if($rResult->role_code == 'VLTS'){
                    return 'vl-data';
                }else if($rResult->role_code == 'MGMT'){
                    return 'home';
                }
                else if($rResult->role_code != 'admin'){
                    return 'recency';
                }else{
                    return 'facilities';
                }
            }else {
                $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
                return 'login';
            }
        }else {
            $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
            return 'login';
        }
    }

    public function fetchUserDetails($parameters) {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');
        $common = new CommonService();
        $aColumns = array('u.user_name','r.role_name','u.email','u.server_password','u.alt_email','u.mobile','u.alt_mobile','u.job_responsibility','u.comments','u.status');
        $orderColumns = array('u.user_name','r.role_name','u.email','u.server_password','u.alt_email','u.mobile','u.alt_mobile','u.job_responsibility','u.comments','u.status');

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
          $iFilteredTotal = count($tResult);
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
              $row[] = $aRow['mobile'];
              $row[] = $aRow['job_responsibility'];
              $row[] = ucwords($aRow['status']);
              $row[] = '<a href="/user/edit/' . base64_encode($aRow['user_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
              $output['aaData'][] = $row;
        }

        return $output;
    }

    public function fetchAllUserDetails(){
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery =  $sql->select()->from('users')
                        ->where(array('status' => 'active'));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function addUserDetails($params)
    {
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        if(isset($params['userName']) && trim($params['userName'])!="")
        {
            $config = new \Zend\Config\Reader\Ini();
            $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
            $password = sha1($params['servPass'] . $configResult["password"]["salt"]);
            $data = array(
                'user_name' => $params['userName'],
                'role_id' => base64_decode($params['roleName']),
                'email' => $params['email'],
                'server_password' => $password,
                'alt_email' => $params['altEmail'],
                'mobile' => $params['mobile'],
                'alt_mobile' => $params['altMobile'],
                'job_responsibility' => $params['JobResponse'],
                'comments' => $params['comments'],
                'status' => $params['userStatus'],
                'web_access' => $params['webAccess'],
                'qc_sync_in_days'=>$params['noOfDays']



            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
            if($lastInsertedId > 0)
            {
                if($params['selectedMapFacility']!=''){
                    $mapArray = explode(",",$params['selectedMapFacility']);
                    foreach($mapArray as $facilityId){
                        $mapData = array(
                            'user_id' => $lastInsertedId,
                            'facility_id' => $facilityId
                        );
                        $mapDb->insert($mapData);
                    }
                }
            }
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
        //facility map
        $umQuery = $sql->select()->from(array('um' => 'user_facility_map'))
                ->join(array('f'=>'facilities'),'f.facility_id=um.facility_id',array('facility_name'))
                ->where(array('um.user_id' => $userId));
        $umQueryStr = $sql->getSqlStringForSqlObject($umQuery);
        $rResult['facilityMap'] = $dbAdapter->query($umQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateUserDetails($params)
    {
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);

        if(isset($params['userId']) && trim($params['userId'])!="")
        {
            $data = array(
                'user_name' => $params['userName'],
                'role_id' => base64_decode($params['roleName']),
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'mobile' => $params['mobile'],
                'alt_mobile' => $params['altMobile'],
                'job_responsibility' => $params['JobResponse'],
                'comments' => $params['comments'],
                'status' => $params['userStatus'],
                'web_access' => $params['webAccess'],
                'qc_sync_in_days'=>$params['noOfDays']

            );
            if($params['servPass']!=''){
                $password = sha1($params['servPass'] . $configResult["password"]["salt"]);
                $data['server_password'] = $password;
            }
            $updateResult = $this->update($data,array('user_id'=>base64_decode($params['userId'])));
            $lastInsertedId = base64_decode($params['userId']);

            $mapDb->delete("user_id=" . $lastInsertedId);
            if($params['selectedMapFacility']!=''){
                $mapArray = explode(",",$params['selectedMapFacility']);
                foreach($mapArray as $facilityId){
                    $mapData = array(
                        'user_id' => $lastInsertedId,
                        'facility_id' => $facilityId
                    );
                    $mapDb->insert($mapData);
                }
            }
        }
        return $lastInsertedId;
    }

    //login by api
    public function userLoginApi($params)
    {
        $common = new CommonService();
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $globalDb = new \Application\Model\GlobalConfigTable($this->adapter);
        $password = sha1($params['password'] . $configResult["password"]["salt"]);

        $sQuery = $sql->select()->from(array('u' => 'users'))
                ->where(array('email' =>$params['email'], 'server_password' => $password))
                ;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        if(isset($rResult['user_id']) && $rResult['user_id']!='' && $rResult['status']=='active') {
            $auth = $common->generateRandomString(15);
            // \Zend\Debug\Debug::dump($rResult['user_id']);die;
            $id = $this->update(array('auth_token'=>$auth),array('user_id'=>$rResult['user_id']));
            if($id>0){
                $response['status']='success';
                $response["userDetails"] = array(
                    'userId' => $rResult->user_id,
                    'userName' => $rResult->user_name,
                    'userEmailAddress' => $rResult->email,
                    'noOfDays'=>$rResult->qc_sync_in_days,
                    'authToken' => $auth
                );
                $response["message"] = "Logged in successfully";
            }else{
                $response["status"] = "fail";
                $response["message"] = "Please try again!";
            }
        } else if($rResult['status']=='inactive'){
            $adminEmail = $globalDb->getGlobalValue('admin_email');
            $adminPhone = $globalDb->getGlobalValue('admin_phone');
            $response['message'] = 'Your password has expired or has been locked, please contact your administrator('.$adminEmail.' or '.$adminPhone.')';
            $response['status'] = 'fail';
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your login credentials!";
        }
       return $response;
    }
    public function updateProfile($params)
    {

        
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        
        if(isset($params['userId']) && trim($params['userId'])!="")
        {
            $data = array(
                'user_name' => $params['userName'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'mobile' => $params['mobile'],
                'alt_mobile' => $params['altMobile'],
                'job_responsibility' => $params['JobResponse'],
                'comments' => $params['comments'],
            );
            if($params['servPass']!=''){
                $password = sha1($params['servPass'] . $configResult["password"]["salt"]);
                $data['server_password'] = $password;
            }

            $updateResult = $this->update($data,array('user_id'=>base64_decode($params['userId'])));
            $lastInsertedId = base64_decode($params['userId']);
        }
        return $lastInsertedId;
    }
}
?>
