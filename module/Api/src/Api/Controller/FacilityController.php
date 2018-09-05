<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class FacilityController extends AbstractRestfulController
{
    public function get($userId)
    {
        $facilityService = $this->getServiceLocator()->get('FacilitiesService');
        $response = $facilityService->getAllFacilityListApi($userId);
        return new JsonModel($response);
    }
}