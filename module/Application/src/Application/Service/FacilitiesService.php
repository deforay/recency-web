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
        $acl = $this->sm->get('AppAcl');
        return $facilityDb->fetchFacilitiesDetails($params,$acl);
    }

    public function addFacilitiesDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('FacilitiesTable');
            $result = $facilityDb->addFacilitiesDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Facility details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Facility details-add';
                $action                 = 'Added  Facility details for Facility id '.$result;
                $resourceName           = 'Facility  Details ';
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

    public function getFacilitiesDetailsById($facilityId)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesDetailsById($facilityId);
    }

    public function updateFacilitiesDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('FacilitiesTable');
            $result = $facilityDb->updateFacilitiesDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Facility details updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Facility details-edit';
                $action                 = 'Edited  Facility details for Facility id '.$result;
                $resourceName           = 'Facility  Details ';
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

    public function getFacilitiesAllDetails()
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesAllDetails();
    }

    public function fetchTestingHubs()
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchTestingHubs();
    }
    
   
    public function getAllFacilityListApi($params)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesDetailsApi($params);
    }
    public function getFacilityByLocation($params)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilityByLocation($params);
    }

    
    public function getTestingFacilitiesTypeDetails()
    {
        $facilityDb = $this->sm->get('TestingFacilityTypeTable');
        return $facilityDb->fetchTestingFacilitiesTypeDetails();
    }

    public function getFacilitiesByFacilityId($facilityId)
    {
        $facilityDb = $this->sm->get('FacilitiesTable');
        return $facilityDb->fetchFacilitiesByFacilityId($facilityId);
    }

    
}


