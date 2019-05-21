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

class DistrictService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getDistrictDetails($params)
    {
        $districteDb = $this->sm->get('DistrictTable');
        return $districteDb->fetchAllDistrictDetails($params);
    }

    public function addDistrictDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $districteDb = $this->sm->get('DistrictTable');
            $result = $districteDb->addDistrictDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'District details added successfully';
            }

        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getDistrictDetailsById($districtId)
    {
        $districteDb = $this->sm->get('DistrictTable');
        return $districteDb->fetchDistrictDetailsById($districtId);
    }

    public function updateDistrictDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $districteDb = $this->sm->get('DistrictTable');
            $result = $districteDb->updateDistrictDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'District details updated successfully';
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
