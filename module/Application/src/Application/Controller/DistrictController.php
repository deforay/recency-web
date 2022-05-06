<?php

namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class DistrictController extends AbstractActionController
{

    private $globalConfigService = null;
    private $districtService = null;
    private $provinceService = null;

    public function __construct($districtService,$provinceService, $globalConfigService)
    {
        $this->provinceService = $provinceService;
        $this->districtService = $districtService;
        $this->globalConfigService = $globalConfigService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->districtService->getDistrictDetails($params);
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
            
            $result = $this->districtService->addDistrictDetails($params);
            // \Zend\Debug\Debug::dump($params);die;
            return $this->_redirect()->toRoute('district');
        } else {
            
            $provinceResult = $this->provinceService->getProvince();
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'provinceResult' => $provinceResult,
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

    public function editAction()
    {
        
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $result = $this->districtService->updateDistrictDetails($params);
            return $this->redirect()->toRoute('district');
        } else {
            $districtId = base64_decode($this->params()->fromRoute('id'));
            $result = $this->districtService->getDistrictDetailsById($districtId);
            
            $provinceResult = $this->provinceService->getProvince();
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'result' => $result,
                'provinceResult' => $provinceResult,
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }
}
