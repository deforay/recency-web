<?php

namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class VlDataController extends AbstractActionController
{
    private $recencyService = null;
    private $facilitiesService = null;
    private $globalConfigService = null;
    private $qualityCheckService = null;

    public function __construct($recencyService, $facilitiesService, $globalConfigService, $qualityCheckService)
    {
        $this->recencyService = $recencyService;
        $this->facilitiesService = $facilitiesService;
        $this->globalConfigService = $globalConfigService;
        $this->qualityCheckService = $qualityCheckService;
    }
    
    public function indexAction()
    {
        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
        ));
    }

    public function getSampleDataAction()
    {
        $result = "";
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getSampleData($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
        return $viewModel;
    }

    public function updateVlSampleResultAction()
    {
        $result = "";
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->updateVlSampleResult($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
        return $viewModel;
    }

    public function recentInfectionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getAllRecencyResultWithVlList($params);

            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function ltInfectionAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getAllLtResult($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function exportRInfectedDataAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->exportRInfectedData($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function exportLongTermInfectedDataAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->exportLongTermInfected($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function TatReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getTatReport($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function exportTatReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->exportTatReport($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
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
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            if (isset($params['pdfFile'])) {
                $result = $this->recencyService->updateEmailSendResult($params);
                return $this->redirect()->toUrl('/vl-data/email-result');
            } else {
                $result = $this->recencyService->getEmailSendResult($params);
                $this->recencyService->UpdateMultiplePdfUpdatedDate($params);
                
                $globalConfigResult = $this->globalConfigService->fetchGlobalConfig();
                //\Zend\Debug\Debug::dump(count($result));die;
                return new ViewModel(array(
                    'result' => $result,
                    'globalConfigResult' => $globalConfigResult,
                    'formFields' => json_encode($params)
                ));
            }
        }
    }
    public function downloadResultPdfAction()
    {
        $id = $this->params()->fromRoute('id');
        return new ViewModel(array(
            'fileName' => $id
        ));
    }
    public function emailResultSamplesAction()
    {
        /** @var \Laminas\Http\Request $request */
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

    public function uploadResultAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->recencyService->uploadResult($params);
            return $this->redirect()->toUrl('/vl-data');
        }
    }

    public function WeeklyReportAction()
    {
        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult
        ));
    }

    public function getWeeklyReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getWeeklyReport($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function exportWeeklyReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->exportWeeklyReport($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function generateRecentPdfAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getRecentDetailsForPDF($params['recencyId']);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    public function generateLTermPdfAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getLTermDetailsForPDF($params['recencyId']);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function qcReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->qualityCheckService->getQualityCheckReportDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function requestVlTestOnVlsmAction()
    {
        $syn = false;
        
        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        foreach ($globalConfigResult as $result) {
            if ($result['global_name'] == 'recency_to_vlsm_sync' && $result['global_value'] == 'no') {
                return $this->redirect()->toUrl('/vl-data');
            }
        }
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->recencyService->postReqVlTestOnVlsmDetails($params);
            return $this->redirect()->toUrl('/vl-data');
        } else {
            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function getVlOnVlsmSampleAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getReqVlTestOnVlsmDetails($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))->setTerminal(true);
            return $viewModel;
        }
    }

    public function ageWiseInfectionReportAction()
    {
        $sessionLogin = new Container('credo');
        /* if($sessionLogin->roleCode != 'admin' || $sessionLogin->roleCode != 'manager'){
            return $this->redirect()->toRoute('home');
        } */

        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
        $result = $this->recencyService->getModalityDetails();
        $testingFacility = $this->facilitiesService->getTestingFacilitiesTypeDetails();

        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
            'testingFacility' => $testingFacility,
            'result' => $result
        ));
    }
    
    public function getAgeWiseInfectionReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $this->recencyService->getModalityDetails($params)))->setTerminal(true);
            return $viewModel;
        }
    }

    public function exportModalityAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $this->recencyService->exportModalityDetails($params)))->setTerminal(true);
            return $viewModel;
        }
    }
}
