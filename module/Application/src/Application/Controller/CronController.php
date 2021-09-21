<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CronController extends AbstractActionController{

    public function indexAction(){
       
    }
    public function sendMailAction(){
      $temp = $this->getServiceLocator()->get('CommonService');
      $temp->sendTempMail();
    }
//update term and final outcome
    public function updateOutcomeAction()
    {
      $service = $this->getServiceLocator()->get('RecencyService');
      $service->updateOutcome();
    }

    public function vlsmSyncAction()
    {
      $service = $this->getServiceLocator()->get('RecencyService');
      $service->vlsmSync();
    }
}