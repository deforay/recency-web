<?php

namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class ManifestsController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            //\Zend\Debug\Debug::dump($params);die;
            $manifestsService = $this->getServiceLocator()->get('ManifestsService');
            $result = $manifestsService->getManifests($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function addAction()
    {

    }
    public function editAction()
    {

    }
}
