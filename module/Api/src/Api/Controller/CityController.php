<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

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
