<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class LoginController extends AbstractRestfulController
{
    public function create($params) {
        //  $plugin = $this->HasParams();
      //if($plugin->checkParams($params,array('mobile','password'))){
            $customerService = $this->getServiceLocator()->get('UserService');
            $response = $customerService->userLoginApi($params);
            return new JsonModel($response);
    //      }else{
    //     $response['status']='fail';
    //     $response['message']='Some required parameters are missing. Please try again.';
    //     return new JsonModel($response);
    //    }
    }
}
