<?php

namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

class UserTable extends AbstractTableGateway
{

    protected $table = 'users';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchLoginUserDetials()
    {
        $logincontainer = new Container('credo');
        return $this->select(array('user_id' => $logincontainer->userId))->current();
    }

    public function loginProcessDetails($params, $configResult)
    {
        $alertContainer = new Container('alert');
        $logincontainer = new Container('credo');
        $captchaSession = new Container('captcha');
        $crossLoginSession = new Container('crossLogin');
        $common = new CommonService();
        $crossLoginSession->logged = false;

        $userLoginHistoryDb = new \Application\Model\UserLoginHistoryTable($this->adapter);

        if (!isset($captchaSession) || empty($captchaSession->status) || $captchaSession->status == 'fail') {
            //User log details
            $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
            $alertContainer->alertMsg = 'Please check if you entered the text from image correctly';
            return 'login';
        }
        /* Cross login credential check */
        if ((isset($params['u']) && $params['u'] != "") && (isset($params['t']) && $params['t'] != "") && $configResult['vlsm']['crosslogin']) {
            $params['u'] = $params['u'];
            $params['t'] = $params['t'];
            $decryptedPassword = CommonService::decrypt($params['t'], base64_decode($configResult['vlsm']['crosslogin-salt']));
            $params['loginPassword'] = $decryptedPassword;
            $params['userName'] = base64_decode($params['u']);
        } elseif (!$configResult['vlsm']['crosslogin'] && !isset($params['userName']) && trim($params['userName']) == "") {
            //User log details
            $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
            $alertContainer->alertMsg = 'Cross login not activated in recency!';
            return 'login';
        }

        if (isset($params['userName']) && trim($params['userName']) != "" && trim($params['loginPassword']) != "") {
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $globalDb = new \Application\Model\GlobalConfigTable($this->adapter);
            $userFacilityMapDb = new \Application\Model\UserFacilityMapTable($this->adapter);


            /* Hash alg */
            $sQuery = $sql->select()->from(array('u' => 'users'))
                ->join(array('r' => 'roles'), 'u.role_id = r.role_id', array('role_code'))
                ->where(array('u.email' => $params['userName'], 'u.web_access' => 'yes'));
            $sQueryStr = $sql->buildSqlString($sQuery);
            $userRow = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if ($userRow['hash_algorithm'] == 'sha1' && !isset($params['u'])) {
                $password = sha1($params['loginPassword'] . $configResult["password"]["salt"]);
                if ($password == $userRow['server_password']) {
                    $newPassword = $common->passwordHash($params['loginPassword']);
                    $this->update(
                        array(
                            'hash_algorithm' => 'phb',
                            'server_password' => $newPassword
                        ),
                        array('user_id' => $userRow['user_id'])
                    );
                } else {
                    //User log details
                    $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
                    $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
                    return 'login';
                }
            } elseif ($userRow['hash_algorithm'] == 'phb') {
                if (!password_verify($params['loginPassword'], $userRow['server_password'])) {
                    //User log details
                    $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
                    $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
                    return 'login';
                }
            }

            if ($userRow) {
                if ($userRow->status == 'inactive') {
                    $adminEmail = $globalDb->getGlobalValue('admin_email');
                    $adminPhone = $globalDb->getGlobalValue('admin_phone');
                    //User log details
                    $userLoginHistoryDb->addUserLoginHistory($params, 'locked');
                    $alertContainer->alertMsg = 'Your password has expired or has been locked, please contact your administrator(' . $adminEmail . ' or ' . $adminPhone . ')';
                    return 'login';
                }
                $ufmResult = $userFacilityMapDb->select(array('user_id' => $userRow->user_id))->toArray();
                $ufmdata = array();
                foreach ($ufmResult as $val) {
                    $ufmdata[] = $val['facility_id'];
                }
                $logincontainer->userId = $userRow->user_id;
                $logincontainer->roleId = $userRow->role_id;
                $logincontainer->roleCode = $userRow->role_code;
                $logincontainer->userName = ucwords($userRow->user_name);
                $logincontainer->userEmail = ucwords($userRow->email);
                $logincontainer->facilityMap = $ufmdata === [] ? null : implode(',', $ufmdata);

                $logincontainer->crossLoginPass = null;
                if (!empty($configResult['vlsm']['crosslogin']) && $configResult['vlsm']['crosslogin'] === true) {
                    $logincontainer->crossLoginPass = $common->encrypt($params['loginPassword'], base64_decode($configResult['vlsm']['crosslogin-salt']));
                }

                if (trim($userRow->role_code) != "remote_order_user") {
                    $nonRemoteUserQuery = $sql->select()->from(array('r' => 'recency'))
                        ->columns(array('count' => new Expression('COUNT(*)')))
                        ->where(array('term_outcome = "" OR  term_outcome = NULL '));

                    if ($logincontainer->facilityMap != null) {
                        $nonRemoteUserQuery = $nonRemoteUserQuery->where('r.facility_id IN (' . $logincontainer->facilityMap . ')');
                    }
                    $alertNonRemoteUserQueryStr = $sql->buildSqlString($nonRemoteUserQuery);

                    $alertNonRemoteUserQueryResult = $dbAdapter->query($alertNonRemoteUserQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                    if (isset($alertNonRemoteUserQueryResult['count']) && $alertNonRemoteUserQueryResult['count'] > 0) {
                        //$logincontainer->nonRemoteUserMsg = 'There are ' . $alertNonRemoteUserQueryResult['count'] . ' pending Recency Assay Tests ';
                    } else {
                        $logincontainer->nonRemoteUserMsg = '';
                    }
                }
                // VL Pending result alert
                $alertQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('count' => new Expression('COUNT(*)')))
                    ->join(array('ufm' => 'user_facility_map'), 'r.facility_id=ufm.facility_id', array())
                    ->join(array('u' => 'users'), 'ufm.user_id=u.user_id', array())
                    ->where(array('term_outcome' => 'Assay Recent', 'vl_result IS NULL', 'u.user_id' => $logincontainer->userId));
                $alertQueryStr = $sql->buildSqlString($alertQuery);
                $alertResult = $dbAdapter->query($alertQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
                if (isset($alertResult['count']) && $alertResult['count'] > 0) {
                    $alertContainer->alertMsg = 'There are ' . $alertResult['count'] . ' recent result(s) without Viral Load result recorded';
                }
                //User log details
                $userLoginHistoryDb->addUserLoginHistory($params, 'successful');
                if ($userRow->role_code == 'VLTS') {
                    return 'vl-data';
                } elseif ($userRow->role_code != 'admin') {
                    return 'recency';
                } elseif ($userRow->role_code == 'manager') {
                    return 'recency';
                } else {
                    return 'recency';
                }
            } else {
                //User log details
                $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
                $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
                return 'login';
            }
        } else {
            //User log details
            $userLoginHistoryDb->addUserLoginHistory($params, 'failed');
            $alertContainer->alertMsg = 'The email id or password that you entered is incorrect';
            return 'login';
        }
    }

    public function fetchUserDetails($parameters, $acl)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');
        $aColumns = array('u.user_name', 'r.role_name', 'u.email', 'u.server_password', 'u.alt_email', 'u.mobile', 'u.alt_mobile', 'u.job_responsibility', 'u.comments', 'u.status');
        $orderColumns = array('u.user_name', 'r.role_name', 'u.email', 'u.server_password', 'u.alt_email', 'u.mobile', 'u.alt_mobile', 'u.job_responsibility', 'u.comments', 'u.status');

        /* Paging */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /* Ordering */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                    $sOrder .= $aColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }
        /* Individual column filtering */
        $counter = count($aColumns);

        /* Individual column filtering */
        for ($i = 0; $i < $counter; $i++) {
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
        $roleId = $sessionLogin->roleId;

        $sQuery = $sql->select()->from(array('u' => 'users'))
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

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        //   echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($tResult);
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        $roleCode = $sessionLogin->roleCode;
        $update = (bool) $acl->isAllowed($roleCode, 'Application\Controller\UserController', 'edit');

        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = ucwords($aRow['user_name']);
            $row[] = ucwords($aRow['role_name']);
            $row[] = $aRow['email'];
            $row[] = $aRow['mobile'];
            $row[] = $aRow['job_responsibility'];
            $row[] = ucwords($aRow['status']);
            if ($update) {
                $row[] = '<a href="/user/edit/' . base64_encode($aRow['user_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            }
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function fetchAllUserDetails()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery =  $sql->select()->from('users')
            ->where(array('status' => 'active'));
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function addUserDetails($params)
    {
        $common = new CommonService();
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        if (isset($params['userName']) && trim($params['userName']) != "") {
            $password = $common->passwordHash($params['servPass']);
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
                'hash_algorithm' => 'phb',
                'qc_sync_in_days' => $params['noOfDays']
            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
            if ($lastInsertedId > 0 && $params['selectedMapFacility'] != '') {
                $mapArray = explode(",", $params['selectedMapFacility']);
                foreach ($mapArray as $facilityId) {
                    $mapData = array(
                        'user_id' => $lastInsertedId,
                        'facility_id' => base64_decode($facilityId)
                    );
                    $mapDb->insert($mapData);
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
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        //facility map
        $umQuery = $sql->select()->from(array('um' => 'user_facility_map'))
            ->join(array('f' => 'facilities'), 'f.facility_id=um.facility_id', array('facility_name', 'facility_type_id'))
            ->where(array('um.user_id' => $userId));
        $umQueryStr = $sql->buildSqlString($umQuery);
        $rResult['facilityMap'] = $dbAdapter->query($umQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateUserDetails($params, $configResult)
    {
        $common = new CommonService();
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);

        if (isset($params['userId']) && trim($params['userId']) != "") {
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
                'qc_sync_in_days' => $params['noOfDays']

            );
            if ($params['servPass'] != '') {
                if (!empty($configResult['vlsm']['crosslogin']) && $configResult['vlsm']['crosslogin'] === true) {
                    $newPass = $common->encrypt($params['servPass'], base64_decode($configResult['vlsm']['crosslogin-salt']));
                    $client = new \GuzzleHttp\Client();
                    $url = rtrim($configResult['vlsm']['domain'], "/");
                    $result = $client->post($url . '/users/editProfileHelper.php', [
                        'form_params' => [
                            'u' => $params['email'],
                            't' => $newPass
                        ]
                    ]);
                    $response = json_decode($result->getBody()->getContents());
                    if (isset($response->status) && $response->status != 'success') {
                        error_log('VLSM profile not updated');
                    }
                }
                $data['server_password'] = $common->passwordHash($params['servPass']);
                $data['hash_algorithm'] = 'phb';
            }
            $this->update($data, array('user_id' => base64_decode($params['userId'])));
            $lastInsertedId = base64_decode($params['userId']);

            $mapDb->delete("user_id=" . $lastInsertedId);
            if ($params['selectedMapFacility'] != '') {
                $mapArray = explode(",", $params['selectedMapFacility']);
                foreach ($mapArray as $facilityId) {
                    $mapData = array(
                        'user_id' => $lastInsertedId,
                        'facility_id' => base64_decode($facilityId)
                    );
                    $mapDb->insert($mapData);
                }
            }
        }
        return $lastInsertedId;
    }

    //login by api
    public function userLoginApi($params, $configResult)
    {
        if (isset($params['email']) && !empty($params['email']) && isset($params['password']) && !empty($params['password'])) {
            $common = new CommonService();
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $globalDb = new \Application\Model\GlobalConfigTable($this->adapter);
            $password = sha1($params['password'] . $configResult["password"]["salt"]);
            /* Hash alg */
            $sQuery = $sql->select()->from(array('u' => 'users'))->where(array('email' => $params['email']));
            $sQueryStr = $sql->buildSqlString($sQuery);
            $userRow = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if ($userRow['hash_algorithm'] == 'sha1') {
                if ($password == $userRow['server_password']) {
                    $newPassword = $common->passwordHash($params['password']);
                    $this->update(
                        array(
                            'hash_algorithm' => 'phb',
                            'server_password' => $newPassword
                        ),
                        array('user_id' => $userRow['user_id'])
                    );
                } else {
                    $response["status"] = "fail";
                    $response["message"] = 'The email id or password that you entered is incorrect';
                    return $response;
                }
            } elseif ($userRow['hash_algorithm'] == 'phb') {
                if (!password_verify($params['password'], $userRow['server_password'])) {
                    $response["status"] = "fail";
                    $response["message"] = 'The email id or password that you entered is incorrect';
                    return $response;
                }
            }

            if (isset($userRow['user_id']) && $userRow['user_id'] != '' && $userRow['status'] == 'active') {
                $auth = $common->generateRandomString(16);
                $secretKey = $userRow->secret_key != '' ? $userRow->secret_key : $common->generateRandomString(32);
                // \Zend\Debug\Debug::dump($rResult['user_id']);die;
                $id = $this->update(array('auth_token' => $auth, 'secret_key' => $secretKey), array('user_id' => $userRow['user_id']));
                if ($id > 0) {
                    $response['status'] = 'success';
                    $response["userDetails"] = array(
                        'userId' => $userRow->user_id,
                        'userName' => $userRow->user_name,
                        'userEmailAddress' => $userRow->email,
                        'noOfDays' => $userRow->qc_sync_in_days,
                        'authToken' => $auth,
                        'secretKey' => $secretKey
                    );
                    $response["message"] = "Logged in successfully";
                } else {
                    $response["status"] = "fail";
                    $response["message"] = "Please try again!";
                }
            } elseif ($userRow['status'] == 'inactive') {
                $adminEmail = $globalDb->getGlobalValue('admin_email');
                $adminPhone = $globalDb->getGlobalValue('admin_phone');
                $response['message'] = 'Your password has expired or has been locked, please contact your administrator(' . $adminEmail . ' or ' . $adminPhone . ')';
                $response['status'] = 'fail';
            } else {
                $response["status"] = "fail";
                $response["message"] = "Please check your login credentials!";
            }
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your login credentials!";
        }
        return $response;
    }
    public function updateProfile($params, $configResult)
    {

        $common = new CommonService();
        if (isset($params['userId']) && trim($params['userId']) != "") {
            $data = array(
                'user_name' => $params['userName'],
                'email' => $params['email'],
                'alt_email' => $params['altEmail'],
                'mobile' => $params['mobile'],
                'alt_mobile' => $params['altMobile'],
                'job_responsibility' => $params['JobResponse'],
                'comments' => $params['comments'],
            );
            if ($params['servPass'] != '' && (!empty($configResult['vlsm']['crosslogin']) && $configResult['vlsm']['crosslogin'] === true)) {
                $client = new \GuzzleHttp\Client();
                $url = rtrim($configResult['vlsm']['domain'], "/");
                $result = $client->post($url . '/users/editProfileHelper.php', [
                    'form_params' => [
                        'u' => $params['email'],
                        't' => sha1($params['servPass'] . $configResult["password"]["salt"])
                    ]
                ]);
                $response = json_decode($result->getBody()->getContents());
                if (isset($response->status) && $response->status != 'success') {
                    error_log('VLSM profile not updated for the user->' . $params['userName']);
                }
                $newPass = $common->passwordHash($params['servPass']);
                $data['server_password'] = $newPass;
                $data['hash_algorithm'] = 'phb';
            }
            $updateResult = $this->update($data, array('user_id' => base64_decode($params['userId'])));
        }
        return $updateResult;
    }

    public function updatePasswordFromVLSMAPI($params, $configResult)
    {
        $upId = 0;
        $response = array();
        $common = new CommonService();
        $check = $this->select(array('email' => $params['u']))->current();
        if ($check) {
            $decryptedPassword = $common->decrypt($params['t'], base64_decode($configResult['vlsm']['crosslogin-salt']));
            $passwordHash = $common->passwordHash($decryptedPassword);
            $data = array(
                'email' => $params['u'],
                'hash_algorithm' => 'phb',
                'server_password' => $passwordHash
            );
            $upId = $this->update($data, array('user_id' => $check['user_id']));
            if ($upId > 0) {
                $response['status'] = "success";
                $response['message'] = "Profile updated successfully!";
            } else {
                $response['status'] = "fail";
                $response['message'] = "Profile not updated!";
            }
        } else {
            $response['status'] = "fail";
            $response['message'] = "Profile not updated!";
        }
        return $response;
    }

    public function fetchUserDetailsByauthToken($authToken)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('u' => 'users'))
            ->join(array('r' => 'roles'), 'u.role_id = r.role_id')
            ->where(array('u.auth_token' => $authToken))
            ->where(array('u.status' => 'active'));
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }
}
