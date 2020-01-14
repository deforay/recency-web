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

class GlobalConfigService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getGlobalConfigDetails($parameters)
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchGlobalConfigDetails($parameters);
    }

    public function getGlobalConfigAllDetails()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchGlobalConfigAllDetails();
    }

    public function fetchGlobalConfig()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchGlobalConfig();
    }    

    public function getGlobalConfigAllDetailsApi()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchGlobalConfigAllDetailsApi();
    }

    public function updateGlobalConfigDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $globalConfigDb = $this->sm->get('GlobalConfigTable');
            $result = $globalConfigDb->updateGlobalConfigDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Global Config details updated successfully';
                 // Add Event log
                 $subject                = $result;
                 $eventType              = 'Global Config details-edit';
                 $action                 = 'Edited  Global Config details';
                 $resourceName           = 'Global Config  Details ';
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

    public function getRecencyMandatoryDetailsApi()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchRecencyMandatoryDetailsApi();
    }
    
    public function getRecencyHideDetailsApi()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchRecencyHideDetailsApi();
    }

    
    public function getTechnicalSupportDetailsApi()
    {
        $globalConfigDb = $this->sm->get('GlobalConfigTable');
        return $globalConfigDb->fetchTechnicalSupportDetailsApi();
    }
}
?>
