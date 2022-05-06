<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class RiskPopulationsController extends AbstractRestfulController
{
    private $riskPopulationsService = null;
    public function __construct($riskPopulationsService)
    {
        $this->riskPopulationsService = $riskPopulationsService;
    }
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $response = $this->riskPopulationsService->getAllRiskPopulationsListApi($params);
        return new JsonModel($response);
    }
}