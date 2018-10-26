<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class GlobalConfigController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
        $response = $globalConfigService->getGlobalConfigAllDetailsApi();
        return new JsonModel($response);
    }
}
