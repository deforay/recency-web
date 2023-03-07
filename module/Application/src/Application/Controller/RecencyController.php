<?php

namespace Application\Controller;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;

use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class RecencyController extends AbstractActionController
{
   private $recencyService = null;
   private $facilitiesService = null;
   private $globalConfigService = null;
   private $settingsService = null;

   public function __construct($recencyService, $facilitiesService, $globalConfigService, $settingsService)
   {
      $this->recencyService = $recencyService;
      $this->facilitiesService = $facilitiesService;
      $this->globalConfigService = $globalConfigService;
      $this->settingsService = $settingsService;
   }
   public function indexAction()
   {
      $logincontainer = new Container('credo');
      $userId = $logincontainer->userId;
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();

      if ($request->isPost()) {
         $params = $request->getPost();
         $result = $this->recencyService->getRecencyDetails($params);

         return $this->getResponse()->setContent(Json::encode($result));
      } else {


         $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
         $manageColumnsResult = $this->recencyService->getAllManagaColumnsDetails($userId);

         $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();

         return new ViewModel(array(
            'facilityResult' => $facilityResult,
            'manageColumnsResult' => $manageColumnsResult,
            'globalConfigResult' => $globalConfigResult,
         ));
      }
   }

   public function addAction()
   {
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();

      if ($request->isPost()) {
         $params = $request->getPost();
         $this->recencyService->addRecencyDetails($params);
         return $this->redirect()->toRoute('recency');
      } else {

         $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
         $testFacilityTypeResult = $this->facilitiesService->getTestingFacilitiesTypeDetails();


         $sampleId = $this->recencyService->getSampleId();
         $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
         $kitInfo = $this->settingsService->getKitLotDetails();
         $sampleInfo = $this->settingsService->getSamplesDetails();
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

      if ($this->getRequest()->isPost()) {
         $params = $this->getRequest()->getPost();
         //echo"<pre>";var_dump($params);die;
         $result = $this->recencyService->updateRecencyDetails($params);
         return $this->redirect()->toRoute('recency');
      } else {
         $recencyId = base64_decode($this->params()->fromRoute('id'));




         $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
         $result = $this->recencyService->getRecencyDetailsById($recencyId);
         $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
         $testFacilityTypeResult = $this->facilitiesService->getTestingFacilitiesTypeDetails();
         $kitInfo = $this->settingsService->getKitLotDetails();
         $sampleInfo = $this->settingsService->getSamplesDetails();
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

      $recencyNo = base64_decode($this->params()->fromRoute('id'));
      $result = $this->recencyService->getRecencyOrderDetails($recencyNo);
      // \Zend\Debug\Debug::dump($result);die;

      $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();


      $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();

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

      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $val = $params['query'];

         $result = $this->recencyService->getTesterData($val);
         return $this->getResponse()->setContent(Json::encode($result));
      }
   }
   public function exportRecencyAction()
   {
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $result = $this->recencyService->exportRecencyData();
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function getLocationBasedFacilityAction()
   {
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getLocationBasedFacility($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function generatePdfAction()
   {
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $this->recencyService->UpdatePdfUpdatedDate($params['recencyId']);
         $result = $this->recencyService->getRecencyDetailsForPDF($params['recencyId']);

         $globalConfigResult = $this->globalConfigService->fetchGlobalConfig();
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result, 'globalConfigResult' => $globalConfigResult));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }


   public function getRecencyAllDataCountAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecencyAllDataCount($params);
      }
      $viewModel = new ViewModel();
      return $viewModel->setVariables(array('result' => json::encode($result)))->setTerminal(true);
   }

   public function getFinalOutcomeChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function mapManageColumnsAction()
   {
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->mapManageColumnsDetails($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result));
         $viewModel->setTerminal(true);
         return $viewModel;
      }
   }

   public function getRecencyLabActivityChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecencyLabActivityChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getTesterWiseFinalOutcomeChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getTesterWiseFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getTesterWiseInvalidChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getTesterWiseInvalidChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getFacilityWiseInvalidChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getFacilityWiseInvalidChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getLotChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getLotChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByGenderChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentInfectionByGenderChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByDistrictChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentInfectionByDistrictChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByAgeChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentInfectionByAgeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentHivViralLoadChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getModalityWiseFinalOutcomeChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getModalityWiseFinalOutcomeChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getRecentInfectionBySexAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentInfectionBySexLineChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getDistrictWiseMissingViralloadAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getDistrictWiseMissingViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getModalityWiseMissingViralloadAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getModalityWiseMissingViralLoadChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function recentInfectionByMonthSexChartAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecentInfectionByMonthSexChart($params);
      }
      $viewModel = new ViewModel();
      $viewModel->setVariables(array('result' => $result))
         ->setTerminal(true);
      return $viewModel;
   }

   public function getKitLotInfoAction()
   {
      $result = "";
      /** @var \Laminas\Http\Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getKitInfo($params['kitNo']);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => Json::encode($result)))->setTerminal(true);
         return $viewModel;
      }
   }
}
