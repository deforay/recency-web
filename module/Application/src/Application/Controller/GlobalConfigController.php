<?php
namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class GlobalConfigController extends AbstractActionController
{

    private $globalConfigService = null;

    public function __construct($globalConfigService)
    {
        $this->globalConfigService = $globalConfigService;
    }
    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            
            $result = $this->globalConfigService->getGlobalConfigDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function editAction()
    {
        
        if($this->getRequest()->isPost())
        {
            $params=$this->getRequest()->getPost();
            $result=$this->globalConfigService->updateGlobalConfigDetails($params);
            return $this->redirect()->toRoute('global-config');
        }
        else
        {
            $globalConfigResult=$this->globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

}
