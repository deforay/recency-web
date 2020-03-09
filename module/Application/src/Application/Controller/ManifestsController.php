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

            $manifestCode = strtoupper('REC' . date('ymd') .  $commonService->generateRandomString(6));
            $sampleList = $recencyService->getSamplesWithoutManifestCode();

            return new ViewModel(array(
                'manifestCode' => $manifestCode,
                'sampleList' => $sampleList
            ));
        }
    }
    public function editAction()
    {
    }
}
