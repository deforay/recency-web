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

class SettingsService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getSettingsDetails($params)
    {
        $settingsDb = $this->sm->get('SettingsTable');
        return $settingsDb->fetchSettingsDetails($params);
    }

    public function addSettingsDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsDb = $this->sm->get('SettingsTable');
            $result = $settingsDb->addSettingsDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Settings details added successfully';
            }

        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getSettingsDetailsById($testId)
    {
        $settingsDb = $this->sm->get('SettingsTable');
        return $settingsDb->fetchSettingsDetailsById($testId);
    }

    public function updateSettingsDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsDb = $this->sm->get('SettingsTable');
            $result = $settingsDb->updateSettingsDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Settings details updated successfully';
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
