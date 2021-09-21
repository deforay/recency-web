<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class PrintResultsController extends AbstractActionController
{

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getPrintResultsDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel();
    }
}
