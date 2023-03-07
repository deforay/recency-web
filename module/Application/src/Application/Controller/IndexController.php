<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Session\Container;

class IndexController extends AbstractActionController
{

    private $recencyService = null;
    private $facilitiesService = null;
    private $globalConfigService = null;

    public function __construct($recencyService, $facilitiesService, $globalConfigService)
    {
        $this->recencyService = $recencyService;
        $this->facilitiesService = $facilitiesService;
        $this->globalConfigService = $globalConfigService;
    }
    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            $result = $this->recencyService->getAllRecencyResult($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {

            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();

            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }
    public function exportRecencyDataAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            if (isset($params['comingFrom']) && trim($params['comingFrom']) == 'district') {
                $result = $this->recencyService->exportDistrictRecencyData($params);
            } else {
                $result = $this->recencyService->fetchExportRecencyData($params);
            }

            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }



    public function  getRecencyAllDataCountAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            $result = $this->recencyService->getRecencyAllDataCount($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function analysisDashboardAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            $result = $this->recencyService->getAllRecencyResult($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {

            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();

            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function qualityControlDashboardAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            $result = $this->recencyService->getAllRecencyResult($params);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {

            $globalConfigResult = $this->globalConfigService->getGlobalConfigAllDetails();

            $facilityResult = $this->facilitiesService->getFacilitiesAllDetails();

            return new ViewModel(array(
                'globalConfigResult' => $globalConfigResult,
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function setSampleFirstChartAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $viewModel = new ViewModel($params);
            return $viewModel->setVariables(array('result' => $params))->setTerminal(true);
        }
    }
}
