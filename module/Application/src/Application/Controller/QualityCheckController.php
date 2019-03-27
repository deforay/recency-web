<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class QualityCheckController extends AbstractActionController
{

     public function indexAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->getQualityCheckDetails($params);
               return $this->getResponse()->setContent(Json::encode($result));
          }
     }

     public function addAction()
     {
          $request = $this->getRequest();
          if ($request->isPost()) {
               $params = $request->getPost();
               $qcService = $this->getServiceLocator()->get('QualityCheckService');
               $result = $qcService->addQcTestDetails($params);
               return $this->_redirect()->toRoute('quality-check');
          }
     }

     public function editAction()
     {

          $qcService = $this->getServiceLocator()->get('QualityCheckService');
          if($this->getRequest()->isPost())
          {
               $params=$this->getRequest()->getPost();
               $result=$qcService->updateQualityCheckDetails($params);
               return $this->redirect()->toRoute('quality-check');
          }
          else
          {
               $qualityCheckId=base64_decode( $this->params()->fromRoute('id') );
               $result=$qcService->getQualityCheckDetailsById($qualityCheckId);
               return new ViewModel(array(
                    'result' => $result,
               ));
          }
     }

     public function viewAction()
     {

          $qcService=$this->getServiceLocator()->get('QualityCheckService');
          $qualityCheckId=base64_decode($this->params()->fromRoute('id'));

          $result=$qcService->getQcDetails($qualityCheckId);

          if ($result) {
               return new ViewModel(array(
                    'result' => $result,
               ));

          } else {
               return $this->redirect()->toRoute("quality-check");
          }

     }

     public function exportQcDataAction()
     {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $qcService = $this->getServiceLocator()->get('QualityCheckService');
            $result=$qcService->exportQcData($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
     }

     public function getQualityCheckVolumeChartAction()
     {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
          $params = $request->getPost();
          $qcService = $this->getServiceLocator()->get('QualityCheckService');
          $result=$qcService->getQualityCheckVolumeChart($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
     }
}
