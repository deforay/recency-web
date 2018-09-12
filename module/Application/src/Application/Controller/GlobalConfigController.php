<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class GlobalConfigController extends AbstractActionController
{

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $result = $globalConfigService->getGlobalConfigDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function editAction()
    {
        $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
        if($this->getRequest()->isPost())
        {
            $params=$this->getRequest()->getPost();
            $result=$globalConfigService->updateGlobalConfigDetails($params);
            return $this->redirect()->toRoute('global-config');
        }
        else
        {
            $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }
}
