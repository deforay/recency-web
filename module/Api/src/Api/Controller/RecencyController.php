<?php

namespace Api\Controller;

use Laminas\View\Model\JsonModel;
use Application\Service\RecencyService;
use Laminas\Mvc\Controller\AbstractRestfulController;

class RecencyController extends AbstractRestfulController
{
    private RecencyService $recencyService = null;

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

    public function create($params)
    {
        $response = $this->recencyService->addRecencyDataApi($params);
        return new JsonModel($response);
    }
}
