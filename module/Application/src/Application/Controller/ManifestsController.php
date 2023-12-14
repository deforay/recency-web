<?php

namespace Application\Controller;

use Laminas\Http\Request;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class ManifestsController extends AbstractActionController
{

    private $manifestsService = null;
    private $facilitiesService = null;
    private $recencyService = null;
    private $globalConfigService = null;
    private $commonService = null;

    public function __construct($manifestsService, $recencyService, $facilitiesService, $globalConfigService, $commonService)
    {
        $this->manifestsService = $manifestsService;
        $this->facilitiesService = $facilitiesService;
        $this->recencyService = $recencyService;
        $this->globalConfigService = $globalConfigService;
        $this->commonService = $commonService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            //\Zend\Debug\Debug::dump($params);die;

            $result = $this->manifestsService->getManifests($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function addAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {

            $params = $request->getPost();
            $result = $this->manifestsService->addManifest($params);
            return $this->redirect()->toRoute('manifests');
        } else {

            $testingHubs = $this->facilitiesService->fetchTestingHubs();

            $manifestCode = strtoupper('R' . date('ymd') .  $this->commonService->generateRandomString(8));
            //$sampleList = $this->recencyService->getSamplesWithoutManifestCode();

            return new ViewModel(array(
                'manifestCode' => $manifestCode,
                //'sampleList' => $sampleList,
                'testingHubs' => $testingHubs
            ));
        }
    }
    public function editAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {

            $params = $request->getPost();
            $result = $this->manifestsService->updateManifest($params);
            return $this->redirect()->toRoute('manifests');
        } else {

            $manifestId = base64_decode($this->params()->fromRoute('id'));
            if (isset($manifestId) && !empty($manifestId)) {

                $testingHubs = $this->facilitiesService->fetchTestingHubs();
                $manifestData = $this->manifestsService->fetchManifestById($manifestId);
                $selectedSamples = $this->recencyService->fetchSamplesByManifestId($manifestId);
                return new ViewModel(array(
                    'manifestData' => $manifestData,
                    'testingHubs' => $testingHubs,
                    'selectedSamples' => $selectedSamples,
                ));
            } else {
                return $this->redirect()->toRoute('manifests');
            }
        }
    }

    public function genarateManifestAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $id = base64_decode($this->params()->fromRoute('id'));

        $result = $this->manifestsService->getManifestsPDF($id);
        $globalConfigResult = $this->globalConfigService->fetchGlobalConfig();
        if (count($result) == 0) {
            $alertContainer = new Container('alert');
            $alertContainer->alertMsg = 'Unable to generate Specimen Manifest PDF. Please check if there are Samples added.';
            return $this->redirect()->toRoute('manifests');
        }
        // \Zend\Debug\Debug::dump($result);die;
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result, 'globalConfigResult' => $globalConfigResult));

        return $viewModel;
    }
    public function getSamplesByTestingSiteAction()
    {
        $result = "";
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->recencyService->getSamplesWithoutManifestCode($params['testingSite']);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
            ->setTerminal(true);
        return $viewModel;
    }
}
