<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class SettingsController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $settingsService = $this->getServiceLocator()->get('SettingsService');
                $result = $settingsService->getSettingsDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        
    }

    public function addAction()
    {
        $session = new Container('credo');
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $settingsService = $this->getServiceLocator()->get('SettingsService');
                $result = $settingsService->addSettingsDetails($params);
                // \Zend\Debug\Debug::dump($params);die;
                return $this->_redirect()->toRoute('settings');
            }
        
    }

    public function editAction()
    {
        $session = new Container('credo');
     

            $settingsService = $this->getServiceLocator()->get('SettingsService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$settingsService->updateSettingsDetails($params);
                return $this->redirect()->toRoute('settings');
            }
            else
            {
                $testId=base64_decode( $this->params()->fromRoute('id') );
                $result=$settingsService->getSettingsDetailsById($testId);
                return new ViewModel(array(
                    'result' => $result,
                ));
            }
        
    }

}
