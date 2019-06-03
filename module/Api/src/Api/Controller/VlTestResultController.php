<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class VlTestResultController extends AbstractRestfulController
{
    public function create($params) {
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->addVlTestResultApi($params);
        return new JsonModel($response);
    }
}