<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class DistrictController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $districtService = $this->getServiceLocator()->get('DistrictService');
                $result = $districtService->getDistrictDetails($params);
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
                $districtService = $this->getServiceLocator()->get('DistrictService');
                $result = $districtService->addDistrictDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->_redirect()->toRoute('district');
            }
            else
            {
                $ProvinceService = $this->getServiceLocator()->get('ProvinceService');
                $provinceResult = $ProvinceService->getProvince();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'provinceResult' => $provinceResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
            }
    }

    public function editAction()
    {
            $districtService = $this->getServiceLocator()->get('DistrictService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$districtService->updateDistrictDetails($params);
                return $this->redirect()->toRoute('district');
            }
            else
            {
                $districtId=base64_decode( $this->params()->fromRoute('id') );
                $result=$districtService->getDistrictDetailsById($districtId);
                $ProvinceService = $this->getServiceLocator()->get('ProvinceService');
                $provinceResult = $ProvinceService->getProvince();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'result' => $result,
                    'provinceResult' => $provinceResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
            
        }
    }
  
}
