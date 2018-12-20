<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class CityController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $CityCommonService = $this->getServiceLocator()->get('CommonService');
        $response = $CityCommonService->getAllCityListApi($params);
        return new JsonModel($response);
    }


}
