<?php
namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class QualityCheckController extends AbstractActionController
{

     public function indexAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getQualityCheckDetails($params);
               return $this->getResponse()->setContent(Json::encode($result));
          } else {
               $facilityService = $this->getServiceLocator()->get('FacilitiesService');
               $facilityResult = $facilityService->getFacilitiesAllDetails();

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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $qcService->addQcTestDetails($params);
               return $this->_redirect()->toRoute('quality-check');
          } else {
               $facilityService = $this->getServiceLocator()->get('FacilitiesService');
               $settingService = $this->getServiceLocator()->get('SettingsService');
               $facilityResult = $facilityService->getFacilitiesAllDetails();
               $kitInfo = $settingService->getKitLotDetails();
               $sampleInfo = $settingService->getSamplesDetails();
               return new ViewModel(array(
                    'facilityResult' => $facilityResult,
                    'kitInfo' => $kitInfo,
                    'sampleInfo' => $sampleInfo
               ));
          }
     }

     public function editAction()
     {

          $qcService = $this->getServiceLocator()->get('QualityCheckService');
          if ($this->getRequest()->isPost()) {
               $params = $this->getRequest()->getPost();
               $result = $qcService->updateQualityCheckDetails($params);
               return $this->redirect()->toRoute('quality-check');
          } else {
               $qualityCheckId = base64_decode($this->params()->fromRoute('id'));
               $result = $qcService->getQualityCheckDetailsById($qualityCheckId);
               $facilityService = $this->getServiceLocator()->get('FacilitiesService');
               $settingService = $this->getServiceLocator()->get('SettingsService');
               $facilityResult = $facilityService->getFacilitiesAllDetails();
               $kitInfo = $settingService->getKitLotDetails();
               $sampleInfo = $settingService->getSamplesDetails();
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

          $qcService = $this->getServiceLocator()->get('QualityCheckService');
          $qualityCheckId = base64_decode($this->params()->fromRoute('id'));

          $result = $qcService->getQcDetails($qualityCheckId);

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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->exportQcData($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getQualityCheckVolumeChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getQualityResultTermOutcomeChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getKitLotNumberChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getSampleLotChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getTestingQualityNegativeChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getTestingQualityInvalidChart($params);
          }
          $viewModel = new ViewModel();
          return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
     }

     public function getPassRateByFacilityAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getPassedQualityBasedOnFacility($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }
     }

     public function getMonthWiseQualityControlAction()
     {
          $result = "";
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getMonthWiseQualityControlChart($params);
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
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getDistrictWiseQualityCheckInvalid($params);
          }
          $viewModel = new ViewModel();
          $viewModel->setVariables(array('result' => $result))
               ->setTerminal(true);
          return $viewModel;
     }
}
