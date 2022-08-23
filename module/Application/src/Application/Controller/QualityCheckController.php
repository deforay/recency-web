<?php

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class QualityCheckController extends AbstractActionController
{

     private $qualityCheckService = null;
     private $facilitiesService = null;
     private $settingsService = null;

     public function __construct($qualityCheckService, $facilitiesService, $settingsService)
     {
          $this->qualityCheckService = $qualityCheckService;
          $this->facilitiesService = $facilitiesService;
          $this->settingsService = $settingsService;
     }

     public function indexAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getQualityCheckDetails($params);
               return $this->getResponse()->setContent(Json::encode($result));
          } else {
               
               $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

               return new ViewModel(array(
                    'facilityResult' => $facilityResult
               ));
          }
     }

     public function addAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $this->qualityCheckService->addQcTestDetails($params);
               return $this->redirect()->toRoute('quality-check');
          } else {
               
               
               $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
               $kitInfo = $this->settingsService->getKitLotDetails();
               $sampleInfo = $this->settingsService->getSamplesDetails();
               return new ViewModel(array(
                    'facilityResult' => $facilityResult,
                    'kitInfo' => $kitInfo,
                    'sampleInfo' => $sampleInfo
               ));
          }
     }

     public function editAction()
     {

          
          if ($this->getRequest()->isPost()) {
               $params = $this->getRequest()->getPost();
               $result = $this->qualityCheckService->updateQualityCheckDetails($params);
               return $this->redirect()->toRoute('quality-check');
          } else {
               $qualityCheckId = base64_decode($this->params()->fromRoute('id'));
               $result = $this->qualityCheckService->getQualityCheckDetailsById($qualityCheckId);
               
               
               $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
               $kitInfo = $this->settingsService->getKitLotDetails();
               $sampleInfo = $this->settingsService->getSamplesDetails();
               return new ViewModel(array(
                    'result' => $result,
                    'facilityResult' => $facilityResult,
                    'kitInfo' => $kitInfo,
                    'sampleInfo' => $sampleInfo
               ));
          }
     }

     public function viewAction()
     {

          
          $qualityCheckId = base64_decode($this->params()->fromRoute('id'));

          $result = $this->qualityCheckService->getQcDetails($qualityCheckId);

          if ($result) {
               return new ViewModel(array(
                    'result' => $result,
               ));
          } else {
               return $this->redirect()->toRoute("quality-check");
          }
     }

     public function exportQcDataAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->exportQcData($params);
               $viewModel = new ViewModel();
               $viewModel->setVariables(array('result' => $result));
               $viewModel->setTerminal(true);
               return $viewModel;
          }
     }

     public function getQualityCheckVolumeChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getQualityCheckVolumeChart($params);
          }
          $viewModel = new ViewModel();
          $viewModel->setVariables(array('result' => $result))
               ->setTerminal(true);
          return $viewModel;
     }

     public function getQualityResultWiseTermOutcomeChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getQualityResultTermOutcomeChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getKitLotNumberChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getKitLotNumberChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getSampleLotChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getSampleLotChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getTestingQualityNegativeChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getTestingQualityNegativeChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getTestingQualityInvalidChartAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getTestingQualityInvalidChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getPassRateByFacilityAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getPassedQualityBasedOnFacility($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }
     }

     public function getMonthWiseQualityControlAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getMonthWiseQualityControlChart($params);
          }
          $viewModel = new ViewModel();
          $viewModel->setVariables(array('result' => $result))
               ->setTerminal(true);
          return $viewModel;
     }

     public function getDistrictWiseQualityCheckInvalidAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               
               $result = $this->qualityCheckService->getDistrictWiseQualityCheckInvalid($params);
          }
          $viewModel = new ViewModel();
          $viewModel->setVariables(array('result' => $result))
               ->setTerminal(true);
          return $viewModel;
     }
}
