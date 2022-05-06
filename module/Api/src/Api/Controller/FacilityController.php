<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class FacilityController extends AbstractRestfulController
{

    private $facilitiesService = null;

    public function __construct($facilitiesService)
    {
        $this->facilitiesService = $facilitiesService;
    }
    public function getList()
    {
        $params = $this->getRequest()->getQuery();
        $response = $this->facilitiesService->getAllFacilityListApi($params);
        return new JsonModel($response);
    }
}
