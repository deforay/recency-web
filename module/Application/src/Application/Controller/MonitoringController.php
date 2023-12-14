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
    private $facilitiesService = null;

    public function __construct($userService, $globalConfigService, $facilitiesService)
    {
        $this->userService = $userService;
        $this->globalConfigService = $globalConfigService;
        $this->facilitiesService = $facilitiesService;
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

    public function systemAlertsAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $alertType = $this->userService->getAlertType();
        $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->userService->getAllAlertsDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel(array(
            'alertType' => $alertType,
            'facilityResult' => $facilityResult
        ));
    }
    public function updateAlertStatusAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->userService->UpdateAlertStatus($params);
            $result = $this->userService->getAllAlertsDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function apiHistoryAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->userService->getAllTrackApiDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function getApiParamsAction()
    {
        $session = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->userService->getApiParamsDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
}