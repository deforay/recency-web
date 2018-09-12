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