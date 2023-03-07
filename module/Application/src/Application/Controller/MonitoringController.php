<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Session\Container;

class MonitoringController extends AbstractActionController
{
    private $userService = null;
    private $globalConfigService = null;

    public function __construct($userService, $globalConfigService)
    {
        $this->userService = $userService;
        $this->globalConfigService = $globalConfigService;
    }

    public function indexAction()
    {

    }

    public function allUserLoginHistoryAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                
                $result = $this->userService->getLoginHistoryDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
    }

    public function auditTrailAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();
        if ($request->isPost()) {
            $params = $request->getPost();
            
            $result = $this->userService->getAuditRecencyDetails($params);
            return new ViewModel(array(
                'result' => $result,
                'params' => $params,
                'globalConfigResult' => $globalConfigResult,
            ));
        } else {
            
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
        }
    }

    public function userActivityLogAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $eventType = $this->userService->getEventType();
        $users = $this->userService->getAllUserDetails();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                
                $result = $this->userService->getUserActivityLogDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
            return new ViewModel(array(
                'eventType' => $eventType,
                'users' => $users
            ));
    }
}