<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

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
