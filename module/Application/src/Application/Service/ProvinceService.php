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

class ProvinceService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getProvinceDetails($params)
    {
        $provinceDb = $this->sm->get('ProvinceTable');
        return $provinceDb->fetchAllProvinceDetails($params);
    }

    public function addProvinceDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $provinceDb = $this->sm->get('ProvinceTable');
            $result = $provinceDb->addProvinceDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Province details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Province details-add';
                $action                 = 'Added  Province details for Province id '.$result;
                $resourceName           = 'Province  Details ';
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

    public function getProvinceDetailsById($provinceId)
    {
        $provinceDb = $this->sm->get('ProvinceTable');
        return $provinceDb->fetchProvinceDetailsById($provinceId);
    }

    public function updateProvinceDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $provinceDb = $this->sm->get('ProvinceTable');
            $result = $provinceDb->updateProvinceDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Province details updated successfully';
                 // Add Event log
                 $subject                = $result;
                 $eventType              = 'Province details-edit';
                 $action                 = 'Edited  Province details for Province id '.$result;
                 $resourceName           = 'Province  Details ';
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

    public function getProvince()
    {
        $provinceDb = $this->sm->get('ProvinceTable');
        return $provinceDb->fetchProvince();
    }
    
   
    
}

?>
