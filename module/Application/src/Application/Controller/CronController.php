<?php

namespace Application\Controller;

use Application\Service\RecencyService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CronController extends AbstractActionController
{
  /** @var RecencyService $recencyService */
  private $recencyService;
  private $commonService = null;

  public function __construct(RecencyService $recencyService, $commonService)
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
