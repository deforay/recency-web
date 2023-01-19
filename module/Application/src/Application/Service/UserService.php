<?php
namespace Application\Service;

use Exception;
use Laminas\Mail;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;
use Laminas\Mime\Part as MimePart;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mail\Transport\Smtp as SmtpTransport;

class UserService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function loginProcess($params)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->loginProcessDetails($params);
    }

    public function getUserDetails($params)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchUserDetails($params);
    }

    public function getAllUserDetails()
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchAllUserDetails();
    }

    public function addUserDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->addUserDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'User details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'User details-add';
                $action                 = 'Added  User details for User id '.$result;
                $resourceName           = 'User  Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }

        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getUserDetailsById($userId)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchUserDetailsById($userId);
    }

    public function updateUserDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->updateUserDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'User details updated successfully';
                 // Add Event log
                 $subject                = $result;
                 $eventType              = 'User details-edit';
                 $action                 = 'Edited  User details for User id '.$result;
                 $resourceName           = 'User  Details ';
                 $eventLogDb             = $this->sm->get('EventLogTable');
                 $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                 // End Event log
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getRoleAllDetails()
    {
        $roleDb = $this->sm->get('RoleTable');
        return $roleDb->fetchRoleAllDetails();
    }

    public function userLoginApi($params)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->userLoginApi($params);
    }
    public function updatePasswordAPI($params)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->updatePasswordFromVLSMAPI($params);
    }
    public function updateProfile($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->updateProfile($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Profile details updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Profile details-edit';
                $action                 = 'Edited  Profile details for Profile id '.base64_decode($params['userId']);
                $resourceName           = 'Profile  Details ';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getLoginHistoryDetails($params)
    {
        $userHistoryDb = $this->sm->get('UserLoginHistoryTable');
        return $userHistoryDb->fetchUserLoginHistoryDetails($params);
    }

    public function getAuditRecencyDetails($params)
    {
        $auditRecencyDb = $this->sm->get('AuditRecencyTable');
        return $auditRecencyDb->getAuditRecencyDetails($params);
    }
}

?>
