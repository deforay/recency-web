<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class ProvinceController extends AbstractRestfulController
{
    private $commonService = null;
    public function __construct($commonService)
    {
        $this->commonService = $commonService;
    }
    public function getList()
    {
        //$params=$this->getRequest()->getQuery();
        $response = $this->commonService->getAllProvienceListApi();
        return new JsonModel($response);
    }

}
