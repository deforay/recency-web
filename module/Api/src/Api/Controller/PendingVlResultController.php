<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class PendingVlResultController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->getAllPendingVlResultListApi($params);
        return new JsonModel($response);
    }
}
