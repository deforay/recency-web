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

    public function fetchLoginUserDetials(){
        $logincontainer = new Container('credo');
        return $this->select(array('user_id'=>$logincontainer->userId))->current();
    }

    public function loginProcessDetails($params){
		$alertContainer = new Container('alert');
        $logincontainer = new Container('credo');
        $captchaSession = new Container('captcha');
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(!isset($captchaSession->status) || empty($captchaSession->status) || $captchaSession->status == 'fail'){
            $alertContainer->alertMsg = 'Please check if you entered the text from image correctly';
            return 'login';
        }
        /* Cross login credential check */
        if((isset($params['u']) && $params['u'] != "") && (isset($params['t']) && $params['t'] != "") && $configResult['vlsm-crosslogin']){
            $params['u'] = filter_var($params['u'], FILTER_SANITIZE_STRING);
            $params['t'] = filter_var($params['t'], FILTER_SANITIZE_STRING);
            $params['userName'] = base64_decode($params['u']);
            $check = $this->select(array('email'=>$params['userName']))->current();
            if($check){
                $passwordSalt = $check['server_password'].$configResult['vlsm-crosslogin-salt'];
                $params['loginPassword'] = hash('sha256',$passwordSalt);
                if($params['loginPassword'] == $params['t']){
                    $password = $check['server_password'];
                }else{
                    $password = "";
                    $params['loginPassword'] = "";
                }
            }else{
                $params['loginPassword'] = "";
            }
        }else{
            if(!$configResult['vlsm-crosslogin'] && !isset($params['userName']) && trim($params['userName']) ==""){
                $alertContainer->alertMsg = 'Cross login not activated in recency!';
                return 'login';
            }
        }
        if(isset($params['userName']) && trim($params['userName'])!="" && trim($params['loginPassword'])!=""){
            /* Cross login credential check password */
            if((!isset($params['u']) && $params['u'] == "") && (!isset($params['t']) && $params['t'] == "")){
                $password = sha1($params['loginPassword'] . $configResult["password"]["salt"]);
            }
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $globalDb = new \Application\Model\GlobalConfigTable($this->adapter);
            $userFacilityMapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
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
                $ufmResult = $userFacilityMapDb->select(array('user_id'=>$rResult->user_id))->toArray();
                $ufmdata = array();
                foreach($ufmResult as $val){
                    array_push($ufmdata,$val['facility_id']);
                }
                $logincontainer->userId = $rResult->user_id;
                $logincontainer->roleId = $rResult->role_id;
                $logincontainer->roleCode = $rResult->role_code;
                $logincontainer->userName = ucwords($rResult->user_name);
                $logincontainer->userEmail = ucwords($rResult->email);
                $logincontainer->facilityMap = implode(',',$ufmdata);
                // VL Pending result alert
                $alertQuery = $sql->select()->from(array('r'=>'recency'))->columns(array('count'=>new Expression('COUNT(*)')))
                ->join(array('ufm'=>'user_facility_map'),'r.facility_id=ufm.facility_id',array())
                ->join(array('u'=>'users'),'ufm.user_id=u.user_id',array())
                ->where(array('term_outcome' => 'Assay Recent','vl_result IS NULL','u.user_id'=>$logincontainer->userId));
                $alertQueryStr = $sql->getSqlStringForSqlObject($alertQuery);
                $alertResult = $dbAdapter->query($alertQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                if(isset($alertResult['count']) && $alertResult['count'] > 0){
                    $alertContainer->alertMsg = 'There are '.$alertResult['count'].' recent result(s) without Viral Load result recorded';
                }
                if($rResult->role_code == 'VLTS'){
                    return 'vl-data';
                }else if($rResult->role_code == 'MGMT'){
                    return 'home';
                }
                else if($rResult->role_code != 'admin'){
                    return 'recency';
                }else if($rResult->role_code == 'manager'){
                    return 'recency';
                }else{
                    return 'recency';
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
                if($configResult['vlsm-crosslogin']){
                    $client = new \GuzzleHttp\Client();
                    $url = $configResult['vlsm']['domain'];
                    $result = $client->post($url.'users/editProfileHelper.php', [
                        'form_params' => [
                            'u' => $params['email'],
                            't' => sha1($params['servPass'] . $configResult["password"]["salt"])
                        ]
                    ]);
                    $response = json_decode($result->getBody()->getContents());
                    if(isset($response->status) && $response->status != 'success'){
                        error_log('VLSM profile not updated');
                    }
                }
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
                if($configResult['vlsm-crosslogin']){
                    $client = new \GuzzleHttp\Client();
                    $url = $configResult['vlsm']['domain'];
                    $result = $client->post($url.'users/editProfileHelper.php', [
                        'form_params' => [
                            'u' => $params['email'],
                            't' => sha1($params['servPass'] . $configResult["password"]["salt"])
                        ]
                    ]);
                    $response = json_decode($result->getBody()->getContents());
                    if(isset($response->status) && $response->status != 'success'){
                        error_log('VLSM profile not updated for the user->'.$params['userName']);
                    }
                }
                $password = sha1($params['servPass'] . $configResult["password"]["salt"]);
                $data['server_password'] = $password;
            }
            $updateResult = $this->update($data,array('user_id'=>base64_decode($params['userId'])));
        }
        return $updateResult;
    }

    public function updatePasswordFromVLSMAPI($params){
        $upId = 0;$response = array();
        $check = $this->select(array('email'=>$params['u']))->current();
        if($check){
            $data = array(
                'email'=>$params['u'],
                'server_password'=>$params['t']
            );
            $upId = $this->update($data,array('user_id'=>$check['user_id']));
            if($upId > 0){
                $response['status'] = "success";
                $response['message'] = "Profile updated successfully!";
            }else{
                $response['status'] = "fail";
                $response['message'] = "Profile not updated!";
            }
        }else{
            $response['status'] = "fail";
            $response['message'] = "Profile not updated!";
        }
        return $response;
    }
}
?>
