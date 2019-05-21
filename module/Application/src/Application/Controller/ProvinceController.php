<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

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
             
                return new ViewModel(array(
                    'result' => $result,
                ));
            
        }
    }
  
}
