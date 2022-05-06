<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class QualityCheckController extends AbstractRestfulController
{
    private $qualityCheckService = null;
    public function __construct($qualityCheckService)
    {
        $this->qualityCheckService = $qualityCheckService;
    }
    public function create($params) {
        $response = $this->qualityCheckService->addQualityCheckDataApi($params);
        return new JsonModel($response);
    }
}
