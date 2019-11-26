<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class UpdatePasswordController extends AbstractRestfulController
{
    public function create($params)
    {
        $userService = $this->getServiceLocator()->get('UserService');
        return new JsonModel($userService->updatePasswordAPI($params));
    }
}
