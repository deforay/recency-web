<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class CommonController extends AbstractActionController
{

    public function indexAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->checkFieldValidations($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
    public function checkMultipleColumnValueAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->checkMultipleFieldValidations($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
    public function getProvinceAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->getProvinceDetails($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }

    public function getDistrictAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->getDistrictDetails($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
    public function getCityAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->getCityDetails($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }
    public function getFacilityAction()
    {
        $result = "";
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $common = $this->getServiceLocator()->get('CommonService');
            $result = $common->getFacilityDetails($params);
        }
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
        return $viewModel;
    }

    public function setSessionAction(){
        $logincontainer = new Container('credo');
        $logincontainer->nonRemoteUserMsg = '';
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }


}
