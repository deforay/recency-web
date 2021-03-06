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
      } else {
         $facilityService = $this->getServiceLocator()->get('FacilitiesService');

         $facilityResult = $facilityService->getFacilitiesAllDetails();
         $manageColumnsResult = $recencyService->getAllManagaColumnsDetails($userId);
         $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
         $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();

         return new ViewModel(array(
            'facilityResult' => $facilityResult,
            'manageColumnsResult' => $manageColumnsResult,
            'globalConfigResult' => $globalConfigResult,
         ));
      }
   }

   public function addAction()
   {
      $request = $this->getRequest();
      $recencyService = $this->getServiceLocator()->get('RecencyService');
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService->addRecencyDetails($params);
         return $this->_redirect()->toRoute('recency');
      } else {
         $facilityService = $this->getServiceLocator()->get('FacilitiesService');
         $facilityResult = $facilityService->getFacilitiesAllDetails();
         $testFacilityTypeResult = $facilityService->getTestingFacilitiesTypeDetails();
         $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
         $settingService = $this->getServiceLocator()->get('SettingsService');
         $sampleId = $recencyService->getSampleId();
         $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();
         $kitInfo = $settingService->getKitLotDetails();
         $sampleInfo = $settingService->getSamplesDetails();
         return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
            'testFacilityTypeResult' => $testFacilityTypeResult,
            'kitInfo' => $kitInfo,
            'sampleInfo' => $sampleInfo,
            'sampleId' => $sampleId
         ));
      }
   }

   public function editAction()
   {
      $recencyService = $this->getServiceLocator()->get('RecencyService');
      if ($this->getRequest()->isPost()) {
         $params = $this->getRequest()->getPost();
         //echo"<pre>";var_dump($params);die;
         $result = $recencyService->updateRecencyDetails($params);
         return $this->redirect()->toRoute('recency');
      } else {
         $recencyId = base64_decode($this->params()->fromRoute('id'));
         $facilityService = $this->getServiceLocator()->get('FacilitiesService');
         $settingService = $this->getServiceLocator()->get('SettingsService');
         $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
         
         $facilityResult = $facilityService->getFacilitiesAllDetails();
         $result = $recencyService->getRecencyDetailsById($recencyId);
         $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();
         $testFacilityTypeResult = $facilityService->getTestingFacilitiesTypeDetails();
         $kitInfo = $settingService->getKitLotDetails();
         $sampleInfo = $settingService->getSamplesDetails();
         return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
            'testFacilityTypeResult' => $testFacilityTypeResult,
            'kitInfo' => $kitInfo,
            'sampleInfo' => $sampleInfo,
            'result' => $result
         ));
      }
   }

   public function viewAction()
   {
      $recencyService = $this->getServiceLocator()->get('RecencyService');
      $recencyNo = base64_decode($this->params()->fromRoute('id'));
      $result = $recencyService->getRecencyOrderDetails($recencyNo);
      // \Zend\Debug\Debug::dump($result);die;
      $facilityService = $this->getServiceLocator()->get('FacilitiesService');
      $facilityResult = $facilityService->getFacilitiesAllDetails();

      $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
      $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();

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
   public function getTesterAction()
   {

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
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->exportRecencyData($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function getLocationBasedFacilityAction()
   {
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getLocationBasedFacility($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function generatePdfAction()
   {
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $recencyService->UpdatePdfUpdatedDate($params['recencyId']);
         $result = $recencyService->getRecencyDetailsForPDF($params['recencyId']);
         $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
         $globalConfigResult = $globalConfigService->fetchGlobalConfig();
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result, 'globalConfigResult' => $globalConfigResult));
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
         $result = $recencyService->getRecencyAllDataCount($params);
      }
      $viewModel = new ViewModel();
      return $viewModel->setVariables(array('result' => json::encode($result)))->setTerminal(true);
   }

   public function getFinalOutcomeChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function mapManageColumnsAction()
   {
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->mapManageColumnsDetails($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function getRecencyLabActivityChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecencyLabActivityChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getTesterWiseFinalOutcomeChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getTesterWiseFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getTesterWiseInvalidChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getTesterWiseInvalidChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getFacilityWiseInvalidChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getFacilityWiseInvalidChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getLotChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getLotChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByGenderChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentInfectionByGenderChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByDistrictChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentInfectionByDistrictChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByAgeChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentInfectionByAgeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentHivViralLoadChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getModalityWiseFinalOutcomeChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getModalityWiseFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getRecentInfectionBySexAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentInfectionBySexLineChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getDistrictWiseMissingViralloadAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getDistrictWiseMissingViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getModalityWiseMissingViralloadAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getModalityWiseMissingViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByMonthSexChartAction()
   {
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getRecentInfectionByMonthSexChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getKitLotInfoAction(){
      $result = "";
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $recencyService = $this->getServiceLocator()->get('RecencyService');
         $result = $recencyService->getKitInfo($params['kitNo']);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => Json::encode($result)))->setTerminal(true);
         return $viewModel;
      }
   }
}