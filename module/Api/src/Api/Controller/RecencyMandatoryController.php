<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class RecencyMandatoryController extends AbstractRestfulController
{
    private $globalConfigService = null;

    public function __construct($globalConfigService)
    {
        $this->globalConfigService = $globalConfigService;
    }
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        $response = $this->globalConfigService->getRecencyMandatoryDetailsApi();
        return new JsonModel($response);
    }
}
