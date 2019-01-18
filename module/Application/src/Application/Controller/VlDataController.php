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

    public function TatReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getTatReport($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function exportTatReportAction()
    {
       $request = $this->getRequest();
       if($request->isPost())
       {
           $params = $request->getPost();
           $recencyService = $this->getServiceLocator()->get('RecencyService');
           $result=$recencyService->exportTatReport($params);
           $viewModel = new ViewModel();
           $viewModel->setVariables(array('result' =>$result));
           $viewModel->setTerminal(true);
           return $viewModel;
       }
    }

    public function emailResultAction()
    {
        $request = $this->getRequest();
        $recencyService = $this->getServiceLocator()->get('RecencyService');

        $sampleResult = $recencyService->getSampleResult();
        return new ViewModel(array(
            'sampleResult' => $sampleResult,
        ));
    
    }
    public function emailResultPdfAction()
    {
        $request = $this->getRequest();
        $recencyService = $this->getServiceLocator()->get('RecencyService');

        if ($request->isPost()) {
               $params = $request->getPost();
               if(isset($params['pdfFile']))
               {
                $result = $recencyService->updateEmailSendResult($params);
                return $this->_redirect()->toUrl('/vl-data/email-result');
               }else{
               $result = $recencyService->getEmailSendResult($params);
               return new ViewModel(array(
                'result' => $result,
                'formFields'=>json_encode($params)
                ));
            }
        }
    }
    public function downloadResultPdfAction()
    {
        $id = $this->params()->fromRoute('id');
        return new ViewModel(array(
                    'fileName'=>$id
                ));
    }
}
