<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

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
