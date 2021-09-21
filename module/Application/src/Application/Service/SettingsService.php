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
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsDb = $this->sm->get('SettingsTable');
            $result = $settingsDb->addSettingsDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Settings details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Settings details-add';
                $action                 = 'Added  Settings details for Settings id '.$result;
                $resourceName           = 'Settings  Details ';
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

    public function getSettingsDetailsById($testId)
    {
        $settingsDb = $this->sm->get('SettingsTable');
        return $settingsDb->fetchSettingsDetailsById($testId);
    }
    
    public function getKitLotDetails()
    {
        $settingsDb = $this->sm->get('SettingsTable');
        return $settingsDb->fetchKitLotDetails();
    }

    public function updateSettingsDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsDb = $this->sm->get('SettingsTable');
            $result = $settingsDb->updateSettingsDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Settings details updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Settings details-edit';
                $action                 = 'Edited  Settings details for Settings id '.$result;
                $resourceName           = 'Settings  Details ';
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

    // Sample Setting Data's

    public function getSettingsSampleDetails($params)
    {
        $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
        return $settingsQcSampleDb->fetchSettingsSampleDetails($params);
    }


    public function getSettingsSampleDetailsById($sampleId)
    {
        $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
        return $settingsQcSampleDb->fetchSettingsSampleDetailsById($sampleId);
    }
    
    public function getSamplesDetails()
    {
        $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
        return $settingsQcSampleDb->fetchSamples();
    }

    public function addSampleSettingsDetails($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
            $result = $settingsQcSampleDb->addSampleSettingsDetails($params);
            if($result > 0){
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Sample details added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'Sample details-add';
                $action                 = 'Added  Sample details for Sample id '.$result;
                $resourceName           = 'Sample  Details ';
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

    public function updateSampleSettingsDetails($params){
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
            $result = $settingsQcSampleDb->updateSampleSettingsDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Sample details updated successfully';
                 // Add Event log
                 $subject                = $result;
                 $eventType              = 'Sample details-edit';
                 $action                 = 'Edited  Sample details for Sample id '.$result;
                 $resourceName           = 'Sample  Details ';
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
    //  API 
    public function getAllSampleListApi($params)
     {
        $settingsQcSampleDb = $this->sm->get('SettingsQcSampleTable');
         return $settingsQcSampleDb->fetchAllSampleListApi($params);
     }
}
