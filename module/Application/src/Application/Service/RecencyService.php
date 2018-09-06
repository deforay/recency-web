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

class RecencyService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getRecencyDetails($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetails($params);
    }

    public function addRecencyDetails($params)
    {
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->addRecencyDetails($params);
            if($result > 0){
                $adapter->commit();

               // $eventAction = 'Added a new Role Detail with the name as - '.ucwords($params['roleName']);
               // $resourceName = 'Roles';
               // $eventLogDb = $this->sm->get('EventLogTable');
               // $eventLogDb->addEventLog($eventAction, $resourceName);
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Recency details added successfully';
            }

        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getRecencyDetailsById($recencyId)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchRecencyDetailsById($recencyId);
    }

    public function updateRecencyDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $recencyDb = $this->sm->get('RecencyTable');
            $result = $recencyDb->updateRecencyDetails($params);
            if($result > 0){
                $adapter->commit();

                // $eventAction = 'Updated Role Detail with the name as - '.ucwords($params['roleName']);
                //  $resourceName = 'Roles';
                //  $eventLogDb = $this->sm->get('EventLogTable');
                //  $eventLogDb->addEventLog($eventAction, $resourceName);

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Recency details updated successfully';
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getAllRecencyListApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->fetchAllRecencyListApi($params);
    }

    public function addRecencyDataApi($params)
    {
        $recencyDb = $this->sm->get('RecencyTable');
        return $recencyDb->addRecencyDetailsApi($params);
    }
}
?>