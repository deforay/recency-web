<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class LoginController extends AbstractActionController
{
    public \Application\Service\UserService $userService;

    public function __construct($userService)
    {
        $this->userService = $userService;
    }

    public function indexAction()
    {
        $logincontainer = new Container('credo');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            $redirectUrl = $this->userService->loginProcess($params);
        } elseif ($request->getQuery() != "") {
            $params = $this->getRequest()->getQuery();
            $captchaSession = new Container('captcha');
            $captchaSession->status = 'success';
            // bypassing captcha
            if (!isset($params['u']) || $params['u'] == "" || !isset($params['t']) || $params['t'] == "") {
                $viewModel = new ViewModel();
                $viewModel->setTerminal(true);
                return $viewModel;
            } else {
                $redirectUrl = $this->userService->loginProcess($params);
                return $this->redirect()->toRoute($redirectUrl);
            }
        }
        /* Cross login credential check end*/
        if (property_exists($logincontainer, 'userId') && $logincontainer->userId !== null && $logincontainer->userId != "") {
            //$alertContainer = new Container('alert');
            return $this->redirect()->toRoute("recency");
        } else {
            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function logoutAction()
    {
        $logincontainer = new Container('credo');
        $logincontainer->roleId = "";
        $logincontainer->roleCode = "";

        $logincontainer->getManager()->getStorage()->clear('credo');
        return $this->redirect()->toRoute("login");
    }
}
