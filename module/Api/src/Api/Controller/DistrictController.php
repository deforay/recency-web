<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class DistrictController extends AbstractRestfulController
{
    public function getList($params)
    {
        $params=$this->getRequest()->getQuery();
        $DistrictCommonService = $this->getServiceLocator()->get('CommonService');
        $response = $DistrictCommonService->getAllDistrictListApi($params);
        return new JsonModel($response);
    }

}
