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
