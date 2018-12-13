<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

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