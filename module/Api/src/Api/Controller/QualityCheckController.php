<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class QualityCheckController extends AbstractRestfulController
{

    public function create($params) {
        $qcService = $this->getServiceLocator()->get('QualityCheckService');
        $response = $qcService->addQualityCheckDataApi($params);
        return new JsonModel($response);
    }
}
