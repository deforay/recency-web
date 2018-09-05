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
            $commonService = $this->getServiceLocator()->get('CommonService');
            $redirectUrl = $commonService->loginProcess($params);
            // \Zend\Debug\Debug::dump($redirectUrl);die;
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

        $logincontainer->getManager()->getStorage()->clear('credo');
        return $this->redirect()->toRoute("login");
    }
}
