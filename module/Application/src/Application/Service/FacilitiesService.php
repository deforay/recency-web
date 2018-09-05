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

class FacilitiesService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getFacilitiesDetails($params)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesDetails($params);
    }

    public function addFacilitiesDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('FacilitiesTable');
            $result = $facilityDb->addFacilitiesDetails($params);
            if($result > 0){
                $adapter->commit();

               // $eventAction = 'Added a new Role Detail with the name as - '.ucwords($params['roleName']);
               // $resourceName = 'Roles';
               // $eventLogDb = $this->sm->get('EventLogTable');
               // $eventLogDb->addEventLog($eventAction, $resourceName);
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Facility details added successfully';
            }

        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getFacilitiesDetailsById($facilityId)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesDetailsById($facilityId);
    }

    public function updateFacilitiesDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('FacilitiesTable');
            $result = $facilityDb->updateFacilitiesDetails($params);
            if($result > 0){
                $adapter->commit();

                // $eventAction = 'Updated Role Detail with the name as - '.ucwords($params['roleName']);
                //  $resourceName = 'Roles';
                //  $eventLogDb = $this->sm->get('EventLogTable');
                //  $eventLogDb->addEventLog($eventAction, $resourceName);

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Facility details updated successfully';
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}

?>
