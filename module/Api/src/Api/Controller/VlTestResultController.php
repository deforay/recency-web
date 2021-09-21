<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class VlTestResultController extends AbstractRestfulController
{
    public function create($params) {
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->addVlTestResultApi($params);
        return new JsonModel($response);
    }
}