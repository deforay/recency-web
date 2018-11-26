<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class LoginController extends AbstractActionController{

    public function indexAction(){
        $logincontainer = new Container('credo');
        $alertContainer = new Container('alert');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            // \Zend\Debug\Debug::dump($params);die;
            $userService = $this->getServiceLocator()->get('UserService');
            $redirectUrl = $userService->loginProcess($params);
            return $this->redirect()->toRoute($redirectUrl);
        }
        if (isset($logincontainer->userId) && $logincontainer->userId != "") {
             $alertContainer = new Container('alert');
            return $this->redirect()->toRoute("home");
        } else {
            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function logoutAction()
    {
        $logincontainer = new Container('credo');
        $alertContainer = new Container('alert');
        $logincontainer->roleId = "";
        $logincontainer->roleCode = ""; 

        $logincontainer->getManager()->getStorage()->clear('credo');
        return $this->redirect()->toRoute("login");
    }
}
