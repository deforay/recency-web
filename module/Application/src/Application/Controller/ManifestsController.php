<?php

namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class ManifestsController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            //\Zend\Debug\Debug::dump($params);die;
            $manifestsService = $this->getServiceLocator()->get('ManifestsService');
            $result = $manifestsService->getManifests($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $manifestService = $this->getServiceLocator()->get('ManifestsService');
            $params = $request->getPost();
            $result = $manifestService->addManifest($params);
            return $this->_redirect()->toRoute('manifests');
        } else {

            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $commonService = $this->getServiceLocator()->get('CommonService');
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');

            $testingHubs = $facilityService->fetchTestingHubs();

            $manifestCode = strtoupper('R' . date('ymd') .  $commonService->generateRandomString(6));
            //$sampleList = $recencyService->getSamplesWithoutManifestCode();

            return new ViewModel(array(
                'manifestCode' => $manifestCode,
                //'sampleList' => $sampleList,
                'testingHubs' => $testingHubs
            ));
        }
    }
    public function editAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $manifestService = $this->getServiceLocator()->get('ManifestsService');
            $params = $request->getPost();
            $result = $manifestService->updateManifest($params);
            return $this->_redirect()->toRoute('manifests');
        } else {

            $manifestId = base64_decode($this->params()->fromRoute('id'));
            if (isset($manifestId) && !empty($manifestId)) {
                $recencyService = $this->getServiceLocator()->get('RecencyService');
                $manifestService = $this->getServiceLocator()->get('ManifestsService');
                $facilityService = $this->getServiceLocator()->get('FacilitiesService');

                $testingHubs = $facilityService->fetchTestingHubs();
                $manifestData = $manifestService->fetchManifestById($manifestId);
                $selectedSamples = $recencyService->fetchSamplesByManifestId($manifestId);
                return new ViewModel(array(
                    'manifestData' => $manifestData,
                    'testingHubs' => $testingHubs,
                    'selectedSamples' => $selectedSamples,
                ));
            } else {
                return $this->_redirect()->toRoute('manifests');
            }
        }
    }

    public function genarateManifestAction()
    {
        $request = $this->getRequest();
        $id = base64_decode($this->params()->fromRoute('id'));
        $manifestService = $this->getServiceLocator()->get('ManifestsService');
        $result = $manifestService->getManifestsPDF($id);
        $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
        $globalConfigResult = $globalConfigService->fetchGlobalConfig();
        if (count($result) == 0) {
            $alertContainer = new Container('alert');
            $alertContainer->alertMsg = 'Unable to generate Specimen Manifest PDF. Please check if there are Samples added.';
            return $this->_redirect()->toRoute('manifests');
        }
        // \Zend\Debug\Debug::dump($result);die;
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result, 'globalConfigResult' => $globalConfigResult));

        return $viewModel;
    }
    public function getSamplesByTestingSiteAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getSamplesWithoutManifestCode($params['testingSite']);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
        return $viewModel;
    }
}
