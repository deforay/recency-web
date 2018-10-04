<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

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