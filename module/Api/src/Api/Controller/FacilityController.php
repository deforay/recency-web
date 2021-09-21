<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class FacilityController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $facilityService = $this->getServiceLocator()->get('FacilitiesService');
        $response = $facilityService->getAllFacilityListApi($params);
        return new JsonModel($response);
    }
}