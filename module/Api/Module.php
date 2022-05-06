<?php

namespace Api;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Api\Controller\Login' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    return new \Api\Controller\LoginController($userService);
                },
                'Api\Controller\UpdatePassword' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    return new \Api\Controller\UpdatePasswordController($userService);
                },
                'Api\Controller\Province' => function ($sm) {
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Api\Controller\ProvinceController($commonService);
                },
                'Api\Controller\District' => function ($sm) {
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Api\Controller\DistrictController($commonService);
                },
                'Api\Controller\QualityCheck' => function ($sm) {
                    $qualityCheckService = $sm->getServiceLocator()->get('QualityCheckService');
                    return new \Api\Controller\QualityCheckController($qualityCheckService);
                },
                'Api\Controller\SampleData' => function ($sm) {
                    $settingsService = $sm->getServiceLocator()->get('SettingsService');
                    return new \Api\Controller\SampleDataController($settingsService);
                },
                'Api\Controller\GlobalConfig' => function ($sm) {
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Api\Controller\GlobalConfigController($globalConfigService);
                },
                'Api\Controller\RiskPopulations' => function ($sm) {
                    $riskPopulationsService = $sm->getServiceLocator()->get('RiskPopulationsService');
                    return new \Api\Controller\RiskPopulationsController($riskPopulationsService);
                },
                'Api\Controller\RecencyMandatory' => function ($sm) {
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Api\Controller\RecencyMandatoryController($globalConfigService);
                },
                'Api\Controller\TechnicalSupport' => function ($sm) {
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Api\Controller\TechnicalSupportController($globalConfigService);
                },
                'Api\Controller\RecencyHide' => function ($sm) {
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Api\Controller\RecencyHideController($globalConfigService);
                },
                'Api\Controller\Facility' => function ($sm) {
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    return new \Api\Controller\FacilityController($facilitiesService);
                },
                'Api\Controller\Recency' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\RecencyController($recencyService);
                },
                'Api\Controller\PendingVlResult' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\PendingVlResultController($recencyService);
                },
                'Api\Controller\RecencySampleid' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\RecencySampleidController($recencyService);
                },
                'Api\Controller\VlTestResult' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\VlTestResultController($recencyService);
                },
                'Api\Controller\TestKitInfo' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\TestKitInfoController($recencyService);
                },
                'Api\Controller\TatReport' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\TatReportController($recencyService);
                },
                'Api\Controller\RecencyResultWithVl' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Api\Controller\RecencyResultWithVlController($recencyService);
                },
                'Api\Controller\City' => function ($sm) {
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Api\Controller\CityController($commonService);
                },
            ),
        );
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
