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

class CityService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getCityDetails($params)
    {
        $cityDb = $this->sm->get('CityTable');
        $acl = $this->sm->get('AppAcl');
        return $cityDb->fetchAllCityDetails($params,$acl);
    }

    public function addCityDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $cityDb = $this->sm->get('CityTable');
            $result = $cityDb->addCityDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'City details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'City details-add';
                $action                 = 'Added  City details for city id '.$result;
                $resourceName           = 'City  Details ';
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

    public function getCityDetailsById($cityId)
    {
        $cityDb = $this->sm->get('CityTable');
        return $cityDb->fetchCityDetailsById($cityId);
    }

    public function updateCityDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $cityDb = $this->sm->get('CityTable');
            $result = $cityDb->updateCityDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'City details updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'City details-edit';
                $action                 = 'Edited  City details for city id '.$result;
                $resourceName           = 'City  Details ';
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

    
}

?>
