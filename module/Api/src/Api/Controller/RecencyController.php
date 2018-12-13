<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class RecencyController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->getAllRecencyListApi($params);
        return new JsonModel($response);
    }

    public function create($params) {
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->addRecencyDataApi($params);
        return new JsonModel($response);
    }
}