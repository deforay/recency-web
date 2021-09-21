<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class DistrictController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $DistrictCommonService = $this->getServiceLocator()->get('CommonService');
        $response = $DistrictCommonService->getAllDistrictListApi($params);
        return new JsonModel($response);
    }

}
