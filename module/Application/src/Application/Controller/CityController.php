<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class CityController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $cityService = $this->getServiceLocator()->get('CityService');
                $result = $cityService->getCityDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }else{
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
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
                $cityService = $this->getServiceLocator()->get('CityService');
                $result = $cityService->addCityDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->_redirect()->toRoute('city');
            }
            else
            {
                $districtService = $this->getServiceLocator()->get('DistrictService');
                $districtResult = $districtService->getCities();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'districtResult' => $districtResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
            }
    }

    public function editAction()
    {
            $cityService = $this->getServiceLocator()->get('CityService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$cityService->updateCityDetails($params);
                return $this->redirect()->toRoute('city');
            }
            else
            {
                $cityId=base64_decode( $this->params()->fromRoute('id') );
                $result=$cityService->getCityDetailsById($cityId);
                $districtService = $this->getServiceLocator()->get('DistrictService');
                $districtResult = $districtService->getCities();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'result' => $result,
                    'districtResult' => $districtResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
            
        }
    }
  
}
