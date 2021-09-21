<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class ProvinceController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $ProvienceCommonService = $this->getServiceLocator()->get('CommonService');
        $response = $ProvienceCommonService->getAllProvienceListApi();
        return new JsonModel($response);
    }

}
