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

class QualityCheckService {

     public $sm = null;

     public function __construct($sm = null) {
          $this->sm = $sm;
     }

     public function getServiceManager() {
          return $this->sm;
     }

     public function getQualityCheckDetails($params)
     {
          $qcTestDb = $this->sm->get('QualityCheckTable');
          return $qcTestDb->fetchQualityCheckDetails($params);
     }

     public function addQcTestDetails($params)
     {
          $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
          $adapter->beginTransaction();
          try {
               $qcTestDb = $this->sm->get('QualityCheckTable');
               $result = $qcTestDb->addQualityCheckTestResultDetails($params);
               if($result > 0){
                    $adapter->commit();
                    $alertContainer = new Container('alert');
                    $alertContainer->alertMsg = 'Quality Check test details added successfully';
               }

          }
          catch (Exception $exc) {
               $adapter->rollBack();
               error_log($exc->getMessage());
               error_log($exc->getTraceAsString());
          }
     }

    public function getQualityCheckDetailsById($qualityCheckId)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->fetchQualityCheckTestDetailsById($qualityCheckId);
    }

    public function updateQualityCheckDetails($params){
        $adapter = $this->sm->get('Zend\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $qcTestDb = $this->sm->get('QualityCheckTable');
            $result = $qcTestDb->updateQualityCheckTestDetails($params);
            if($result > 0){
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Quality Check test details updated successfully';
            }
        }
        catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    public function addQualityCheckDataApi($params)
    {
        $qcTestDb = $this->sm->get('QualityCheckTable');
        return $qcTestDb->addQualityCheckDetailsApi($params);
    }
}
?>
