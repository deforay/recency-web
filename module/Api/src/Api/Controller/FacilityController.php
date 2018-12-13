<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

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