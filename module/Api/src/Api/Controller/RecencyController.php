<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

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
        // echo "test"; die;
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->addRecencyDataApi($params);
        return new JsonModel($response);
    }
}