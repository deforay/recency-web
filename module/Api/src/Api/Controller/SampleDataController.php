<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class SampleDataController extends AbstractRestfulController
{
    private $settingsService = null;
    public function __construct($settingsService)
    {
        $this->settingsService = $settingsService;
    }
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $response = $this->settingsService->getAllSampleListApi($params);
        return new JsonModel($response);
    }


}
