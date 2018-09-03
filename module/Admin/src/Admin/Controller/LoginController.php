<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class LoginController extends AbstractActionController{

    public function indexAction(){
        $logincontainer = new Container('admin_credo');
        $alertContainer = new Container('alert');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $commonService = $this->getServiceLocator()->get('CommonService');
            $redirectUrl = $commonService->loginProcess($params);
            return $this->redirect()->toRoute($redirectUrl);
        }
        if (isset($logincontainer->adminId) && $logincontainer->adminId != "") {
             $alertContainer = new Container('alert');
            return $this->redirect()->toRoute("admin-home");
        } else {
            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function logoutAction()
    {
        $logincontainer = new Container('admin_credo');
        $alertContainer = new Container('alert');

        $logincontainer->getManager()->getStorage()->clear('admin_credo');
        return $this->redirect()->toRoute("admin-login");
    }
}
