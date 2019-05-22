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
        return $cityDb->fetchAllCityDetails($params);
    }

    public function addCityDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $cityDb = $this->sm->get('CityTable');
            $result = $cityDb->addCityDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'City details added successfully';
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
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $cityDb = $this->sm->get('CityTable');
            $result = $cityDb->updateCityDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'City details updated successfully';
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
