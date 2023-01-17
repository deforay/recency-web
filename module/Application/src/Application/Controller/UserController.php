<?php
namespace Application\Controller;

use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class UserController extends AbstractActionController
{
    private $userService = null;
    private $globalConfigService = null;

    public function __construct($userService, $globalConfigService)
    {
        $this->userService = $userService;
        $this->globalConfigService = $globalConfigService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{

            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                
                $result = $this->userService->getuserDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        }
    }

    public function addAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{
            $request = $this->getRequest();
            
            if ($request->isPost()) {
                $params = $request->getPost();
                $result = $this->userService->adduserDetails($params);
                return $this->redirect()->toRoute('user');
            }else{
                $roleResult=$this->userService->getRoleAllDetails();
                
                $globalConfigResult=$this->globalConfigService->getGlobalConfigAllDetails();
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
            return $this->redirect()->toRoute('recency');
        }else{

            
            if($this->getRequest()->isPost())
            {
                $params=$this->getRequest()->getPost();
                $result=$this->userService->updateUserDetails($params);
                return $this->redirect()->toRoute('user');
            }
            else
            {
                $userId=base64_decode( $this->params()->fromRoute('id') );
                if($userId!=''){
                $roleResult=$this->userService->getRoleAllDetails();
                $result=$this->userService->getuserDetailsById($userId);
                
                $globalConfigResult=$this->globalConfigService->getGlobalConfigAllDetails();
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

        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->userService->updateProfile($params);
            return $this->redirect()->toRoute("home");
        }
        else
        {
            $userId=base64_decode( $this->params()->fromRoute('id'));
            if($userId!=''){
            $result=$this->userService->getuserDetailsById($userId);
            return new ViewModel(array(
                'result' => $result,
            ));
            }else{
                return $this->redirect()->toRoute("home");
            }
        }
    }

    public function userLoginHistoryAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                $params['user'] = true;
                $result = $this->userService->getLoginHistoryDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
    }

    public function allUserLoginHistoryAction()
    {
        $session = new Container('credo');
        $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                // \Zend\Debug\Debug::dump($params);die;
                
                $result = $this->userService->getLoginHistoryDetails($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
    }

}
