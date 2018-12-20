<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class VlDataController extends AbstractActionController
{
    public function indexAction()
    {

        $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
        $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
        return new ViewModel(array(
            'globalConfigResult' => $globalConfigResult,
        ));
    }

    public function getSampleDataAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getSampleData($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }

    public function updateVlSampleResultAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->updateVlSampleResult($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }

    public function recentInfectionAction()
    {

        $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->getAllRecencyResultWithVlList($params);

               return $this->getResponse()->setContent(Json::encode($result));

          }else{
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
          }
    }

    public function ltInfectionAction()
    {
        $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $recencyService = $this->getServiceLocator()->get('RecencyService');
               $result = $recencyService->getAllLtResult($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }else{
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult=$globalConfigService->getGlobalConfigAllDetails();
            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
            ));
          }
    }

    public function exportRInfectedDataAction()
    {
       $request = $this->getRequest();
       if($request->isPost())
       {
           $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->exportRInfectedData($params);
           $viewModel = new ViewModel();
           $viewModel->setVariables(array('result' =>$result));
           $viewModel->setTerminal(true);
           return $viewModel;
       }
    }

    public function exportLongTermInfectedDataAction()
    {
       $request = $this->getRequest();
       if($request->isPost())
       {
           $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->exportLongTermInfected($params);
           $viewModel = new ViewModel();
           $viewModel->setVariables(array('result' =>$result));
           $viewModel->setTerminal(true);
           return $viewModel;
       }
    }
}
