<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class SettingsController extends AbstractActionController
{
    private $settingsService = null;

    public function __construct($settingsService)
    {
        $this->settingsService = $settingsService;
    }
    public function indexAction()
    {
        $sessionLogin = new Container('credo');
        if($sessionLogin->roleCode != 'admin'){
            return $this->redirect()->toRoute('home');
        }
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->settingsService->getSettingsDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function sampleDataIndexAction()
    {
        $session = new Container('credo');
            /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                
                $result = $this->settingsService->getSettingsSampleDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
    }

    public function addAction()
    {
        $session = new Container('credo');
            /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                
                $result = $this->settingsService->addSettingsDetails($params);
                return $this->redirect()->toRoute('settings');
            }
    }

    public function editAction()
    {
        $session = new Container('credo');
        
        if($this->getRequest()->isPost())
        {
            $params=$this->getRequest()->getPost();
            $result=$this->settingsService->updateSettingsDetails($params);
            return $this->redirect()->toRoute('settings');
        }
        else
        {
            $testId=base64_decode( $this->params()->fromRoute('id') );
            $result=$this->settingsService->getSettingsDetailsById($testId);
            return new ViewModel(array(
                'result' => $result,
            ));
        }
    }
    public function addSampleAction()
    {
        $session = new Container('credo');
            /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                
                $result = $this->settingsService->addSampleSettingsDetails($params);
                return $this->redirect()->toRoute('settings');
            }
        
    }
    public function editSampleAction()
    {
        $session = new Container('credo');
        
        if($this->getRequest()->isPost())
        {
            $params=$this->getRequest()->getPost();
            $result=$this->settingsService->updateSampleSettingsDetails($params);
            return $this->redirect()->toRoute('settings');
        }
        else
        {
            $sampleId=base64_decode( $this->params()->fromRoute('id') );
            $result=$this->settingsService->getSettingsSampleDetailsById($sampleId);
            return new ViewModel(array(
                'result' => $result,
            ));
        }
    }
}
