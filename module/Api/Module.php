<?php

namespace Api;

use Api\Controller\CityController;
use Api\Controller\DistrictController;
use Api\Controller\FacilityController;
use Api\Controller\GlobalConfigController;
use Api\Controller\LoginController;
use Api\Controller\PendingVlResultController;
use Api\Controller\ProvinceController;
use Api\Controller\QualityCheckController;
use Api\Controller\RecencyController;
use Api\Controller\RecencyHideController;
use Api\Controller\RecencyMandatoryController;
use Api\Controller\RecencyResultWithVlController;
use Api\Controller\RecencySampleidController;
use Api\Controller\RiskPopulationsController;
use Api\Controller\SampleDataController;
use Api\Controller\TatReportController;
use Api\Controller\TechnicalSupportController;
use Api\Controller\TestKitInfoController;
use Api\Controller\UpdatePasswordController;
use Api\Controller\VlTestResultController;

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
                        return new LoginController($userService);
                    }
                },
                'Api\Controller\UpdatePasswordController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        return new UpdatePasswordController($userService);
                    }
                },
                'Api\Controller\ProvinceController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new ProvinceController($commonService);
                    }
                },
                'Api\Controller\DistrictController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new DistrictController($commonService);
                    }
                },
                'Api\Controller\QualityCheckController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $qualityCheckService = $diContainer->get('QualityCheckService');
                        return new QualityCheckController($qualityCheckService);
                    }
                },
                'Api\Controller\SampleDataController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $settingsService = $diContainer->get('SettingsService');
                        return new SampleDataController($settingsService);
                    }
                },
                'Api\Controller\GlobalConfigController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new GlobalConfigController($globalConfigService);
                    }
                },
                'Api\Controller\RiskPopulationsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $riskPopulationsService = $diContainer->get('RiskPopulationsService');
                        return new RiskPopulationsController($riskPopulationsService);
                    }
                },
                'Api\Controller\RecencyMandatoryController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new RecencyMandatoryController($globalConfigService);
                    }
                },
                'Api\Controller\TechnicalSupportController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new TechnicalSupportController($globalConfigService);
                    }
                },
                'Api\Controller\RecencyHideController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new RecencyHideController($globalConfigService);
                    }
                },
                'Api\Controller\FacilityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        return new FacilityController($facilitiesService);
                    }
                },
                'Api\Controller\RecencyController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new RecencyController($recencyService);
                    }
                },
                'Api\Controller\PendingVlResultController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new PendingVlResultController($recencyService);
                    }
                },
                'Api\Controller\RecencySampleidController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new RecencySampleidController($recencyService);
                    }
                },
                'Api\Controller\VlTestResultController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new VlTestResultController($recencyService);
                    }
                },
                'Api\Controller\TestKitInfoController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new TestKitInfoController($recencyService);
                    }
                },
                'Api\Controller\TatReportController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new TatReportController($recencyService);
                    }
                },
                'Api\Controller\RecencyResultWithVlController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new RecencyResultWithVlController($recencyService);
                    }
                },
                'Api\Controller\CityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new CityController($commonService);
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
