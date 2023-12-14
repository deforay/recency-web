<?php

namespace Application\Controller;

use Laminas\Http\Request;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Application\Service\UserService;
use Laminas\Mvc\Controller\AbstractActionController;

class LoginController extends AbstractActionController
{
    public ?UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function indexAction()
    {
        $logincontainer = new Container('credo');
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            $redirectUrl = $this->userService->loginProcess($params);
        } elseif ($request->getQuery() != "") {
            $params = $request->getQuery();
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
        if (!empty($logincontainer->userId)) {
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
