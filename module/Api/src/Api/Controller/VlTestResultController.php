<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class VlTestResultController extends AbstractRestfulController
{

    private $recencyService = null;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
    }
    public function create($params)
    {
        $response = $this->recencyService->addVlTestResultApi($params);
        return new JsonModel($response);
    }
}
