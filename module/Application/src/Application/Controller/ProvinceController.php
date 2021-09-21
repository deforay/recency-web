<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class ProvinceController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $provinceService = $this->getServiceLocator()->get('ProvinceService');
                $result = $provinceService->getProvinceDetails($params);
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
                $provinceService = $this->getServiceLocator()->get('ProvinceService');
                $result = $provinceService->addProvinceDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->_redirect()->toRoute('province');
            }else{
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'globalConfigResult' => $globalConfigResult,
               ));
              }
    }

    public function editAction()
    {
        $session = new Container('credo');
      

            $provinceService = $this->getServiceLocator()->get('ProvinceService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$provinceService->updateProvinceDetails($params);
                return $this->redirect()->toRoute('province');
            }
            else
            {
                $provinceId=base64_decode( $this->params()->fromRoute('id') );
                $result=$provinceService->getProvinceDetailsById($provinceId);
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'result' => $result,
                    'globalConfigResult' => $globalConfigResult,
                ));
            
        }
    }
  
}
