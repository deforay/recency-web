<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class SampleDataController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $SettingsService = $this->getServiceLocator()->get('SettingsService');
        $response = $SettingsService->getAllSampleListApi($params);
        return new JsonModel($response);
    }


}
