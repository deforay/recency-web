<?php
namespace Application\Service;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;

class RoleService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getAllRole($parameters){
        $roleDb = $this->sm->get('RoleTable');
        $acl = $this->sm->get('AppAcl');
        return $roleDb->fetchAllRole($parameters,$acl);
    }
    
    public function addRole($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $roleDb = $this->sm->get('RoleTable');
            $result = $roleDb->addRoleDetails($params);
            if($result > 0){
                $adapter->commit();
                $roleDb->mapRolePrivilege($params);
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Role details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Role details-add';
                $action                 = 'Added  Role details for User id '.$result;
                $resourceName           = 'Role';
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

    public function updateRole($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $roleDb = $this->sm->get('RoleTable');
            $result = $roleDb->updateRoleDetails($params);

            $subject = $result;
            $eventType = 'Role-update';
            $action = 'has Updated the Role Name as - '.ucwords($params['roleName']);
            $resourceName = 'Role';
            $eventLogDb = $this->sm->get('EventLogTable');
            $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);

            if($result>0){
             $adapter->commit();
             $roleDb->mapRolePrivilege($params);
             $alertContainer = new Container('alert');
             $alertContainer->alertMsg = 'Role details updated successfully';
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getRole($roleId){
        $roleDb = $this->sm->get('RoleTable');
        return $roleDb->fetchRole($roleId);
    }
    
    public function getAllResource(){
        $roleDb = $this->sm->get('RoleTable');
        return $roleDb->fetchAllResource();
    }
}


