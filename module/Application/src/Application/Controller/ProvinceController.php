<?php

namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class ProvinceController extends AbstractActionController
{
    private $globalConfigService = null;
    private $provinceService = null;

    public function __construct($provinceService, $globalConfigService)
    {
        $this->provinceService = $provinceService;
        $this->globalConfigService = $globalConfigService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->provinceService->getProvinceDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->provinceService->addProvinceDetails($params);
            // \Zend\Debug\Debug::dump($params);die;
            return $this->_redirect()->toRoute('province');
        } else {
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

    public function editAction()
    {
        $session = new Container('credo');


        
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $result = $this->provinceService->updateProvinceDetails($params);
            return $this->redirect()->toRoute('province');
        } else {
            $provinceId = base64_decode($this->params()->fromRoute('id'));
            $result = $this->provinceService->getProvinceDetailsById($provinceId);
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'result' => $result,
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }
}
