<?php

namespace Api\Controller;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Json\Json;

class TestKitInfoController extends AbstractRestfulController
{
    public function getList()
    {
        $params=$this->getRequest()->getQuery();
        
        $returnResponse = array();
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        $response = $recencyService->getKitInfo($params['kitNo']);
        if(isset($response) && count($response) > 0){
            $returnResponse['status'] = 'success';
            $returnResponse['data'] = $response;
        }else{
            $returnResponse['status'] = 'fail';
            $returnResponse['message'] = 'No test kit lot Number founds!';
        }
        return new JsonModel($returnResponse);
    }
}
