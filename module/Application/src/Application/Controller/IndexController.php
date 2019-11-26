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
        } else {
            $logincontainer = new Container('credo');
            if (isset($logincontainer->roleCode) && $logincontainer->roleCode == "remote_order_user") {
                return $this->redirect()->toRoute("recency");
            }
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult = $facilityService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }
    public function exportRecencyDataAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            if (isset($params['comingFrom']) && trim($params['comingFrom']) == 'district') {
                $result = $recencyService->exportDistrictRecencyData($params);
            } else {
                $result = $recencyService->fetchExportRecencyData($params);
            }

            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }



    public function  getRecencyAllDataCountAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getRecencyAllDataCount($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
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
        } else {
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult = $facilityService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function qualityControlDashboardAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getAllRecencyResult($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $globalConfigService = $this->getServiceLocator()->get('GlobalConfigService');
            $globalConfigResult = $globalConfigService->getGlobalConfigAllDetails();
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult = $facilityService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function setSampleFirstChartAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel($params);
            return $viewModel->setVariables(array('result' => $params))->setTerminal(true);
        }
    }
}
