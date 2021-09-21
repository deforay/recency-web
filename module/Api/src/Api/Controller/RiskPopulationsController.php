<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class RiskPopulationsController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $riskPopulationsService = $this->getServiceLocator()->get('RiskPopulationsService');
        $response = $riskPopulationsService->getAllRiskPopulationsListApi($params);
        return new JsonModel($response);
    }
}