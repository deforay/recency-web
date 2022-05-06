<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class LoginController extends AbstractRestfulController
{
    private $userService = null;
    public function __construct($userService)
    {
        $this->userService = $userService;
    }
    public function create($params)
    {
        $response = $this->userService->userLoginApi($params);
        return new JsonModel($response);
    }
}
