<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

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
