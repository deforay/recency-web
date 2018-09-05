<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class FacilitiesController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $facilityService = $this->getServiceLocator()->get('FacilitiesService');
                $result = $facilityService->getFacilitiesDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        }
    }

    public function addAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                $facilityService = $this->getServiceLocator()->get('FacilitiesService');
                $result = $facilityService->addFacilitiesDetails($params);
                return $this->_redirect()->toRoute('facilities');
            }
        }
    }

    public function editAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{

            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$facilityService->updateFacilitiesDetails($params);
                return $this->redirect()->toRoute('facilities');
            }
            else
            {
                $facilityId=base64_decode( $this->params()->fromRoute('id') );
                // \Zend\Debug\Debug::dump($facilityId);die;
                $result=$facilityService->getFacilitiesDetailsById($facilityId);
                return new ViewModel(array(
                    'result' => $result
                ));
            }
        }
    }
}
