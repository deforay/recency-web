<?php
namespace Application\Service;

use Exception;
use Zend\Mail;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Message as MimeMessage;
use Zend\Mail\Transport\Smtp as SmtpTransport;

class UserService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getUserDetails($params)
    {
        $userDb = $this->sm->get('UserTable');
        return $userDb->fetchUserDetails($params);
    }

    public function addUserDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->addUserDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'User details added successfully';
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
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $userDb = $this->sm->get('UserTable');
            $result = $userDb->updateUserDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'User details updated successfully';
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
}

?>
