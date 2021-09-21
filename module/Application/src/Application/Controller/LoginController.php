<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class LoginController extends AbstractActionController{

    public function indexAction(){
        $logincontainer = new Container('credo');
        $userService = $this->getServiceLocator()->get('UserService');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            $redirectUrl = $userService->loginProcess($params);
        } 
        /* Cross login credential check start*/
        else if($request->getQuery() != ""){
            $params=$this->getRequest()->getQuery();
            $captchaSession = new Container('captcha');
            $captchaSession->status = 'success'; // bypassing captcha            
            if(!isset($params['u']) && $params['u'] == ""){
                $viewModel = new ViewModel();
                $viewModel->setTerminal(true);
                return $viewModel;
            }else{
                $redirectUrl = $userService->loginProcess($params);
                return $this->redirect()->toRoute($redirectUrl);
            }
        }
        /* Cross login credential check end*/
        if (isset($logincontainer->userId) && $logincontainer->userId != "") {
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
