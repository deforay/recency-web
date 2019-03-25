<?php
/**
* Zend Framework (http://framework.zend.com/)
*
* @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
* @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
               $params = $request->getPost();
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->getAllRecencyResult($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }else{
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult=$facilityService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
          }
    }
    public function exportRecencyDataAction()
    {
       $request = $this->getRequest();
       if($request->isPost())
       {
           $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->fetchExportRecencyData($params);
           $viewModel = new ViewModel();
           $viewModel->setVariables(array('result' =>$result));
           $viewModel->setTerminal(true);
           return $viewModel;
       }
    }

   

    public function  getRecencyAllDataCountAction()
    {
       $request = $this->getRequest();
       if($request->isPost())
       {
           $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->getRecencyAllDataCount($params);
           $viewModel = new ViewModel();
           $viewModel->setVariables(array('result' =>$result));
           $viewModel->setTerminal(true);
           return $viewModel;
       }
    }
    
    public function analysisDashboardAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
               $params = $request->getPost();
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->getAllRecencyResult($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }else{
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult=$facilityService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
          }
    }

}

