<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CronController extends AbstractActionController
{
  private $recencyService = null;
  private $commonService = null;

  public function __construct($recencyService, $commonService)
  {
    $this->recencyService = $recencyService;
    $this->commonService = $commonService;
  }

  public function indexAction()
  {
  }
  public function sendMailAction()
  {
    $this->commonService->sendTempMail();
  }
  //update term and final outcome
  public function updateOutcomeAction()
  {
    $this->recencyService->updateOutcome();
  }

  public function vlsmSyncAction()
  {
    $this->recencyService->vlsmSync();
  }

}
