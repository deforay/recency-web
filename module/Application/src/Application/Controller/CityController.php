<?php

namespace Application\Controller;

use Laminas\Http\Request;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class CityController extends AbstractActionController
{
    private $cityService = null;
    private $globalConfigService = null;
    private $districtService = null;

    public function __construct($cityService, $districtService, $globalConfigService)
    {
        $this->cityService = $cityService;
        $this->districtService = $districtService;
        $this->globalConfigService = $globalConfigService;
    }
    public function indexAction()
    {
        $session = new Container('credo');
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->cityService->getCityDetails($params);
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
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->cityService->addCityDetails($params);
            // \Zend\Debug\Debug::dump($params);die;
            return $this->redirect()->toRoute('city');
        } else {
            
            $districtResult = $this->districtService->getCities();
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'districtResult' => $districtResult,
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

    public function editAction()
    {
        
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            $result = $this->cityService->updateCityDetails($params);
            return $this->redirect()->toRoute('city');
        } else {
            $cityId = base64_decode($this->params()->fromRoute('id'));
            $result = $this->cityService->getCityDetailsById($cityId);
            
            $districtResult = $this->districtService->getCities();
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'result' => $result,
                'districtResult' => $districtResult,
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }
}
