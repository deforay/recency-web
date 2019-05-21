<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

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
                return new ViewModel(array(
                    'provinceResult' => $provinceResult,
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
                return new ViewModel(array(
                    'result' => $result,
                    'provinceResult' => $provinceResult,
                ));
            
        }
    }
  
}
