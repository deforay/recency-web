<?php
namespace Application\Controller;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class QualityCheckController extends AbstractActionController
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
                    $qcService = $this->getServiceLocator()->get('QualityCheckService');
                    $result = $qcService->getQualityCheckDetails($params);
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
               // \Zend\Debug\Debug::dump($data);die;
               $request = $this->getRequest();
               if ($request->isPost()) {
                    $params = $request->getPost();
                    $qcService = $this->getServiceLocator()->get('QualityCheckService');
                    $result = $qcService->addQcTestDetails($params);
                    return $this->_redirect()->toRoute('quality-check');
               }
               // else{
               //
               //      $userService = $this->getServiceLocator()->get('UserService');
               //      $userResult = $userService->getAllUserDetails();
               //      $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
               //      $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
               //      return new ViewModel(array(
               //           'userResult' => $userResult,
               //           'globalConfigResult' => $globalConfigResult,
               //      ));
               // }
          }
     }

     public function editAction()
     {
          $session = new Container('credo');
          if($session->roleCode == 'user'){
               return $this->_redirect()->toRoute('recency');
          }else{

               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               if($this->getRequest()->isPost())
               {
                    $params=$this->getRequest()->getPost();
                    $result=$qcService->updateQualityCheckDetails($params);
                    return $this->redirect()->toRoute('quality-check');
               }
               else
               {
                    $qualityCheckId=base64_decode( $this->params()->fromRoute('id') );
                    $result=$qcService->getQualityCheckDetailsById($qualityCheckId);

                    // $userService = $this->getServiceLocator()->get('UserService');
                    // $userResult = $userService->getAllUserDetails();
                    // $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
                    // $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
                    return new ViewModel(array(
                         // 'userResult' => $userResult,
                         'result' => $result,
                         // 'globalConfigResult' => $globalConfigResult,
                    ));
               }
          }
     }

}
