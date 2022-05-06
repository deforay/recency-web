<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
class RecencySampleidController extends AbstractRestfulController
{
    private $recencyService = null;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
    }
    public function getList()
    {
        
        $response = $this->recencyService->getSampleId();
        if ($response) {
            $data['sample-data'] = $response;
            $data['status'] = "success";
        } else {
            $data['sample-data'] = $response;
            $data['status'] = "fail";
        }
        return new JsonModel($data);
    }
}
