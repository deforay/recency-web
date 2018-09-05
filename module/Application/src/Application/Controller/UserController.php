<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class UserController extends AbstractActionController
{

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            // \Zend\Debug\Debug::dump($params);die;
            $userService = $this->getServiceLocator()->get('UserService');
            $result = $userService->getuserDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $userService = $this->getServiceLocator()->get('UserService');
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $userService->adduserDetails($params);
            return $this->_redirect()->toRoute('user');
        }else{
            $roleResult=$userService->getRoleAllDetails();
            return new ViewModel(array(
                'roleResult' => $roleResult
            ));
        }
    }

    public function editAction()
    {
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
            $roleResult=$userService->getRoleAllDetails();
            $result=$userService->getuserDetailsById($userId);
            return new ViewModel(array(
                'result' => $result,
                'roleResult' => $roleResult
            ));
        }
    }
}
