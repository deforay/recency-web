<?php

namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class ReportsController extends AbstractActionController
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