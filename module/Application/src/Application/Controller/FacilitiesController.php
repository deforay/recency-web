<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class FacilitiesController extends AbstractActionController
{

    private $globalConfigService = null;
    private $userService = null;
    private $facilitiesService = null;

    public function __construct($facilitiesService, $userService, $globalConfigService)
    {
        $this->globalConfigService = $globalConfigService;
        $this->facilitiesService = $facilitiesService;
        $this->userService = $userService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                
                $result = $this->facilitiesService->getFacilitiesDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        }
    }

    public function addAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                
                $result = $this->facilitiesService->addFacilitiesDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->redirect()->toRoute('facilities');
            }else{
                
                $userResult = $this->userService->getAllUserDetails();
                
                $globalConfigResult=$this->globalConfigService->getGlobalConfigAllDetails();
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
            return $this->redirect()->toRoute('recency');
        }else{

            
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$this->facilitiesService->updateFacilitiesDetails($params);
                return $this->redirect()->toRoute('facilities');
            }
            else
            {
                $facilityId=base64_decode( $this->params()->fromRoute('id') );
                $result=$this->facilitiesService->getFacilitiesDetailsById($facilityId);
                
                $userResult = $this->userService->getAllUserDetails();
                
                $globalConfigResult=$this->globalConfigService->getGlobalConfigAllDetails();
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
            
            $result = $this->facilitiesService->getFacilityByLocation($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
}
