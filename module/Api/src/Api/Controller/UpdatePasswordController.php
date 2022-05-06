<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class UpdatePasswordController extends AbstractRestfulController
{
    private $userService = null;
    public function __construct($userService)
    {
        $this->userService = $userService;
    }
    public function create($params)
    {
        return new JsonModel($this->userService->updatePasswordAPI($params));
    }
}
