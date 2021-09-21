<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class LoginController extends AbstractRestfulController
{
    public function create($params)
    {

        $customerService = $this->getServiceLocator()->get('UserService');
        $response = $customerService->userLoginApi($params);
        return new JsonModel($response);
    }
}
