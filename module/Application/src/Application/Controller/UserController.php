<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class UserController extends AbstractActionController
{

    public function indexAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                $userService = $this->getServiceLocator()->get('UserService');
                $result = $userService->getuserDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        }
    }

    public function addAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{
            $request = $this->getRequest();
            $userService = $this->getServiceLocator()->get('UserService');
            if ($request->isPost()) {
                $params = $request->getPost();
                $result = $userService->adduserDetails($params);
                return $this->_redirect()->toRoute('user');
            }else{
                $roleResult=$userService->getRoleAllDetails();
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'roleResult' => $roleResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
            }
        }
    }

    public function editAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->_redirect()->toRoute('recency');
        }else{

            $userService = $this->getServiceLocator()->get('UserService');
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$userService->updateUserDetails($params);
                return $this->redirect()->toRoute('user');
            }
            else
            {
                $userId=base64_decode( $this->params()->fromRoute('id') );
                if($userId!=''){
                $roleResult=$userService->getRoleAllDetails();
                $result=$userService->getuserDetailsById($userId);
                $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                return new ViewModel(array(
                    'result' => $result,
                    'roleResult' => $roleResult,
                    'globalConfigResult' => $globalConfigResult,
                ));
                }else{
                    return $this->redirect()->toRoute("user");
                }
            }
        }
    }
    public function editProfileAction()
    {
        $userService = $this->getServiceLocator()->get('UserService');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $userService->updateProfile($params);
            return $this->redirect()->toRoute("home");
        }
        else
        {
            $userId=base64_decode( $this->params()->fromRoute('id'));
            if($userId!=''){
            $result=$userService->getuserDetailsById($userId);
            return new ViewModel(array(
                'result' => $result,
            ));
            }else{
                return $this->redirect()->toRoute("home");
            }
        }
    }

}
