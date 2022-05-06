<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class CityController extends AbstractRestfulController
{
    private $commonService = null;

    public function __construct($commonService)
    {
        $this->commonService = $commonService;
    }
    public function getList()
    {
        $params = $this->getRequest()->getQuery();
        $response = $this->commonService->getAllCityListApi($params);
        return new JsonModel($response);
    }
}
