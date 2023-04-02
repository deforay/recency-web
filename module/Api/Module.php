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
                'Api\Controller\LoginController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        return new \Api\Controller\LoginController($userService);
                    }
                },
                'Api\Controller\UpdatePasswordController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        return new \Api\Controller\UpdatePasswordController($userService);
                    }
                },
                'Api\Controller\ProvinceController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new \Api\Controller\ProvinceController($commonService);
                    }
                },
                'Api\Controller\DistrictController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new \Api\Controller\DistrictController($commonService);
                    }
                },
                'Api\Controller\QualityCheckController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $qualityCheckService = $diContainer->get('QualityCheckService');
                        return new \Api\Controller\QualityCheckController($qualityCheckService);
                    }
                },
                'Api\Controller\SampleDataController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $settingsService = $diContainer->get('SettingsService');
                        return new \Api\Controller\SampleDataController($settingsService);
                    }
                },
                'Api\Controller\GlobalConfigController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new \Api\Controller\GlobalConfigController($globalConfigService);
                    }
                },
                'Api\Controller\RiskPopulationsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $riskPopulationsService = $diContainer->get('RiskPopulationsService');
                        return new \Api\Controller\RiskPopulationsController($riskPopulationsService);
                    }
                },
                'Api\Controller\RecencyMandatoryController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new \Api\Controller\RecencyMandatoryController($globalConfigService);
                    }
                },
                'Api\Controller\TechnicalSupportController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new \Api\Controller\TechnicalSupportController($globalConfigService);
                    }
                },
                'Api\Controller\RecencyHideController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new \Api\Controller\RecencyHideController($globalConfigService);
                    }
                },
                'Api\Controller\FacilityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        return new \Api\Controller\FacilityController($facilitiesService);
                    }
                },
                'Api\Controller\RecencyController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\RecencyController($recencyService);
                    }
                },
                'Api\Controller\PendingVlResultController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\PendingVlResultController($recencyService);
                    }
                },
                'Api\Controller\RecencySampleidController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\RecencySampleidController($recencyService);
                    }
                },
                'Api\Controller\VlTestResultController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\VlTestResultController($recencyService);
                    }
                },
                'Api\Controller\TestKitInfoController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\TestKitInfoController($recencyService);
                    }
                },
                'Api\Controller\TatReportController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\TatReportController($recencyService);
                    }
                },
                'Api\Controller\RecencyResultWithVlController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new \Api\Controller\RecencyResultWithVlController($recencyService);
                    }
                },
                'Api\Controller\CityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new \Api\Controller\CityController($commonService);
                    }
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
