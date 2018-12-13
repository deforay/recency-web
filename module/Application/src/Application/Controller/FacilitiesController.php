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
                $facilityService = $this->getServiceLocator()->get('FacilitiesService');
                $result = $facilityService->addFacilitiesDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->_redirect()->toRoute('facilities');
            }else{
                $userService = $this->getServiceLocator()->get('UserService');
                $userResult = $userService->getAllUserDetails();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'userResult' => $userResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
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
                $result=$facilityService->getFacilitiesDetailsById($facilityId);
                $userService = $this->getServiceLocator()->get('UserService');
                $userResult = $userService->getAllUserDetails();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'userResult' => $userResult,
                    'result' => $result,
                    'globalConfigResult' => $globalConfigResult,
                ));
            }
        }
    }
    public function getFacilityByLocationAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $result = $facilityService->getFacilityByLocation($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
}
