<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class RecencyMandatoryController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
        $response = $globalConfigService->getRecencyMandatoryDetailsApi();
        return new JsonModel($response);
    }
}
