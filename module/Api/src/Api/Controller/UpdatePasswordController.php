<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class UpdatePasswordController extends AbstractRestfulController
{
    public function create($params)
    {
        $userService = $this->getServiceLocator()->get('UserService');
        return new JsonModel($userService->updatePasswordAPI($params));
    }
}
