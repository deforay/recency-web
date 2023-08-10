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
        $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
            'facilityResult' => $facilityResult,
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

}
