<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class RecencyResultWithVlController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->getAllRecencyResultWithVlListApi($params);
        return new JsonModel($response);
    }
}