<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;

class RecencySampleidController extends AbstractRestfulController
{
    public function getList()
    {
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->getSampleId();
        if($response)
        {
            $data['sample-data'] = $response;
            $data['status'] = "success";
        }
        else
        {
            $data['sample-data'] = $response;
            $data['status'] = "fail";
        }
        return new JsonModel($data);
    }
}
