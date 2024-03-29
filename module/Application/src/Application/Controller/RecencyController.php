<?php

namespace Application\Controller;

use Application\Service\RecencyService;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Http\Request;
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
   private $sampleTypesService = null;

   public function __construct($recencyService, $facilitiesService, $globalConfigService, $settingsService, $sampleTypesService)
   {
      $this->recencyService = $recencyService;
      $this->facilitiesService = $facilitiesService;
      $this->globalConfigService = $globalConfigService;
      $this->settingsService = $settingsService;
      $this->sampleTypesService = $sampleTypesService;
   }
   public function indexAction()
   {
      $logincontainer = new Container('credo');
      $userId = $logincontainer->userId;
      /** @var Request $request */
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
      /** @var Request $request */
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
         $sampleTypes = $this->sampleTypesService->getSampleTypesDetails();
         return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
            'testFacilityTypeResult' => $testFacilityTypeResult,
            'kitInfo' => $kitInfo,
            'sampleInfo' => $sampleInfo,
            'sampleId' => $sampleId,
            'sampleTypes' => $sampleTypes
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

         $resultFacility = false;
         foreach($facilityResult['facility'] as $facility){
            if($result['facility_id'] == $facility['facility_id']){
               $resultFacility = true;
            }
         }
         if(!$resultFacility && $result['facility_id'] != ''){
            $fResult = $this->facilitiesService->getFacilitiesByFacilityId($result['facility_id']);
            foreach ($fResult['facility'] as $newFacility) {
               $facilityResult['facility'][] = $newFacility;
            }
         }
         
         $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
         $testFacilityTypeResult = $this->facilitiesService->getTestingFacilitiesTypeDetails();
         $sampleInfo = $this->settingsService->getSamplesDetails();
         $sampleTypes = $this->sampleTypesService->getSampleTypesDetails();
         return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
            'testFacilityTypeResult' => $testFacilityTypeResult,
            'sampleInfo' => $sampleInfo,
            'result' => $result,
            'sampleTypes' => $sampleTypes
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

      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
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
      /** @var Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getKitInfo($params['kitNo']);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => Json::encode($result)))->setTerminal(true);
         return $viewModel;
      }
   }
   public function getRecencyDateBasedTestKitAction()
   {
      /** @var Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();

         $result = $this->recencyService->getRecencyDateBasedTestKit($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
         return $viewModel;
      }
   }
   public function checkPatientIdValidationAction()
   {
      $result = "";
      /** @var Request $request */
      $request = $this->getRequest();
      if ($request->isPost()) {
         $params = $request->getPost();
         $result = $this->recencyService->checkPatientIdValidation($params);
         $viewModel = new ViewModel();
         $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
         return $viewModel;
      }
   }

   public function emailResultAction()
    {
        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,

        ));
    }

    public function emailResultPdfAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            if (isset($params['pdfFile'])) {
                $result = $this->recencyService->updateEmailSendResult($params);
                return $this->redirect()->toUrl('/recency/email-result');
            } else {
                $result = $this->recencyService->getEmailSendResult($params);
                $this->recencyService->UpdateMultiplePdfUpdatedDate($params);
                
                $globalConfigResult = $this->globalConfigService->fetchGlobalConfig();
                return new ViewModel(array(
                    'result' => $result,
                    'globalConfigResult' => $globalConfigResult,
                    'formFields' => json_encode($params)
                ));
            }
        }
    }

    public function emailResultSamplesAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $sampleResult = $this->recencyService->getSampleResult($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('sampleResult' => $sampleResult,));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function downloadResultPdfAction()
    {
        $id = $this->params()->fromRoute('id');
        return new ViewModel(array(
            'fileName' => $id
        ));
    }
}
