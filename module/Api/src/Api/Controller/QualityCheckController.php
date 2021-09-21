<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class QualityCheckController extends AbstractRestfulController
{
    public function create($params) {
        $qcService = $this->getServiceLocator()->get('QualityCheckService');
        $response = $qcService->addQualityCheckDataApi($params);
        return new JsonModel($response);
    }
}
