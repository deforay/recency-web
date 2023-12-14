<?php

namespace Api\Controller;

use Laminas\View\Model\JsonModel;
use Application\Service\RecencyService;
use Laminas\Mvc\Controller\AbstractRestfulController;

class RecencyController extends AbstractRestfulController
{
    private $recencyService;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
    }
    public function getList()
    {
        $params = $this->getRequest()->getQuery();
        $response = $this->recencyService->getAllRecencyListApi($params);
        return new JsonModel($response);
    }

    public function create($data)
    {
        $response = $this->recencyService->addRecencyDataApi($data);
        return new JsonModel($response);
    }
}
