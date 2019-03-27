<?php
namespace Application\Controller;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class RecencyController extends AbstractActionController
{

     public function indexAction()
     {
          $logincontainer = new Container('credo');
          $userId = $logincontainer->userId;
          $request = $this->getRequest();
          $recencyService = $this->getServiceLocator()->get('RecencyService');
          if ($request->isPost()) {
               $params = $request->getPost();
               $result = $recencyService->getRecencyDetails($params);

               return $this->getResponse()->setContent(Json::encode($result));
          }else{
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            
            $facilityResult=$facilityService->getFacilitiesAllDetails();
            $manageColumnsResult=$recencyService->getAllManagaColumnsDetails($userId);
            
            return new ViewModel(array(
                'facilityResult' => $facilityResult,
                'manageColumnsResult' => $manageColumnsResult,
                
           ));
          }
     }

     public function addAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->addRecencyDetails($params);
               
               return $this->_redirect()->toRoute('recency');
          }else{
               $facilityService = $this->getServiceLocator()->get('FacilitiesService');
               $facilityResult=$facilityService->getFacilitiesAllDetails();
               $testFacilityTypeResult=$facilityService->getTestingFacilitiesTypeDetails();
               $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
               $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
               return new ViewModel(array(
                    'globalConfigResult' => $globalConfigResult,
                    'facilityResult' => $facilityResult,
                    'testFacilityTypeResult' => $testFacilityTypeResult,
               ));
          }
     }

     public function editAction()
     {
          $recencyService = $this->getServiceLocator()->get('RecencyService');
          if($this->getRequest()->isPost())
          {
               $params=$this->getRequest()->getPost();
               $result=$recencyService->updateRecencyDetails($params);
               return $this->redirect()->toRoute('recency');
          }
          else
          {
               $recencyId=base64_decode( $this->params()->fromRoute('id') );
               $facilityService = $this->getServiceLocator()->get('FacilitiesService');
           
               $facilityResult=$facilityService->getFacilitiesAllDetails();
             
               $result=$recencyService->getRecencyDetailsById($recencyId);
               $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
               $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
               $testFacilityTypeResult=$facilityService->getTestingFacilitiesTypeDetails();
               return new ViewModel(array(
                    'globalConfigResult' => $globalConfigResult,
                    'facilityResult' => $facilityResult,
                    'testFacilityTypeResult' => $testFacilityTypeResult,
                    'result' => $result
               ));
          }
     }

     public function viewAction()
     {

          $recencyService=$this->getServiceLocator()->get('RecencyService');
          $recencyNo=base64_decode($this->params()->fromRoute('id'));

          $result=$recencyService->getRecencyOrderDetails($recencyNo);

          // \Zend\Debug\Debug::dump($result);die;

          $facilityService = $this->getServiceLocator()->get('FacilitiesService');
          $facilityResult=$facilityService->getFacilitiesAllDetails();
          
          $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
          $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();

          if ($result) {
               return new ViewModel(array(
                    'globalConfigResult' => $globalConfigResult,
                    'result' => $result,
                    'facilityResult' => $facilityResult
               ));

          } else {
               return $this->redirect()->toRoute("recency");
          }

     }
     public function getTesterAction() {

          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $val = $params['query'];
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->getTesterData($val);
               return $this->getResponse()->setContent(Json::encode($result));
          }
     }
     public function exportRecencyAction()
     {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result=$recencyService->exportRecencyData($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
     }

     public function getLocationBasedFacilityAction()
     {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result=$recencyService->getLocationBasedFacility($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
     }

    public function generatePdfAction() {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result=$recencyService->getRecencyDetailsForPDF($params['recencyId']);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
  

    public function getRecencyAllDataCountAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
          $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->getRecencyAllDataCount($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
    public function getFinalOutcomeChartAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
          $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->getFinalOutcomeChart($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }

    public function mapManageColumnsAction()
     {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result=$recencyService->mapManageColumnsDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
     }
    
    
}
