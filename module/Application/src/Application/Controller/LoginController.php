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
        } elseif ($request->getQuery('u', '') != '' && $request->getQuery('t', '') != '') {
            // Cross-login from InteLIS. The token is encrypted with the shared
            // crosslogin-salt key, so loginProcessDetails lets it in without a captcha.
            // Any other query string used to mark the captcha as solved.
            $redirectUrl = $this->userService->loginProcess($request->getQuery());
            return $this->redirect()->toRoute($redirectUrl);
        }
        /* Cross login credential check end*/
        if (!empty($logincontainer->userId)) {
            //$alertContainer = new Container('alert');
            return $this->redirect()->toRoute("recency");
        } else {
            if ($request->isPost()) {
                // A failed sign-in shows the login page again, with the reason in the alert
                $this->getResponse()->setStatusCode(401);
            }
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
