<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class RecencyResultWithVlController extends AbstractRestfulController
{
    private $recencyService = null;
    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
    }
    public function getList()
    {
        $params = $this->getRequest()->getQuery();
        $response = $this->recencyService->getAllRecencyResultWithVlListApi($params);
        return new JsonModel($response);
    }
}
