<?php

namespace Application;


use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Application\Model\Acl;
use Laminas\Http\Response;
use Laminas\Session\Container;
use Application\Model\CityTable;
use Application\Model\RoleTable;
use Application\Model\UserTable;
use Application\Model\RecencyTable;
use Application\Model\DistrictTable;
use Application\Model\EventLogTable;
use Application\Model\ProvinceTable;
use Application\Model\SettingsTable;
use Application\Model\TempMailTable;
use Application\Service\CityService;
use Application\Service\RoleService;
use Application\Service\UserService;
use Laminas\Mvc\ModuleRouteListener;
use Application\Model\ManifestsTable;
use Application\Model\ResourcesTable;
use Application\Model\FacilitiesTable;
use Application\Service\CommonService;
use Application\Model\SampleTypesTable;
use Application\Service\RecencyService;
use Application\Model\AuditRecencyTable;
use Application\Model\GlobalConfigTable;

use Application\Model\QualityCheckTable;
use Application\Model\SystemAlertsTable;

// Models
use Application\Service\DistrictService;
use Application\Service\ProvinceService;
use Application\Service\SettingsService;
use Application\Service\ManifestsService;
use Application\View\Helper\CustomConfig;
use Application\View\Helper\GlobalConfig;
use Application\Controller\CityController;
use Application\Controller\CronController;
use Application\Controller\UserController;
use Application\Service\FacilitiesService;
use Application\Controller\IndexController;
use Application\Controller\LoginController;
use Application\Controller\RolesController;
use Application\Model\RiskPopulationsTable;

use Application\Model\UserFacilityMapTable;
use Application\Service\SampleTypesService;
use Application\View\Helper\UserCrossLogin;
use Application\Controller\CommonController;
use Application\Controller\VlDataController;
use Application\Model\ManageColumnsMapTable;
use Application\Model\SettingsQcSampleTable;
use Application\Model\TrackApiRequestsTable;
use Application\Model\UserLoginHistoryTable;
use Application\Service\GlobalConfigService;
use Application\Service\QualityCheckService;
use Application\Controller\CaptchaController;
// Service

use Application\Controller\RecencyController;
use Application\Controller\ReportsController;
use Application\Controller\DistrictController;
use Application\Controller\ProvinceController;
use Application\Controller\SettingsController;
use Application\Controller\ManifestsController;
use Application\Model\RecencyChangeTrailsTable;
use Application\Model\TestingFacilityTypeTable;
use Application\Service\RiskPopulationsService;
use Application\Controller\FacilitiesController;
use Application\Controller\MonitoringController;
use Application\Controller\GlobalConfigController;
use Application\Controller\PrintResultsController;
use Application\Controller\QualityCheckController;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;


class Module implements ConfigProviderInterface
{
    public function onBootstrap(MvcEvent $e)
    {
        /** @var $application Application */
        $application = $e->getApplication();

        $eventManager        = $application->getEventManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //no need to call presetter if request is from CLI
        if (php_sapi_name() != 'cli') {
            $eventManager->attach('dispatch', function (MvcEvent $e) {
                return $this->preSetter($e);
            }, 100);
        }
    }



    public function preSetter(MvcEvent $e)
    {

        /** @var $application Application */
        $application = $e->getApplication();
        /** @var Request $request */
        $request = $e->getRequest();



        if (
            !$request->isXmlHttpRequest()
            && ($e->getRouteMatch()->getParam('controller') != 'Application\Controller\LoginController') &&
            ($e->getRouteMatch()->getParam('controller') != 'Application\Controller\CaptchaController')
        ) {


            $session = new Container('credo');
            if (empty($session) || empty($session->userId) || empty($session->userId)) {
                $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                /** @var Response $response */
                $response = $e->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);
                $response->sendHeaders();

                // To avoid additional processing
                // we can attach a listener for Event Route with a high priority
                $stopCallBack = function ($event) use ($response) {
                    $event->stopPropagation();
                    return $response;
                };
                //Attach the "break" as a listener with a high priority
                $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                return $response;
            } else {
                $diContainer = $application->getServiceManager();
                $viewModel = $application->getMvcEvent()->getViewModel();
                $acl = $diContainer->get('AppAcl');
                $viewModel->acl = $acl;
                $session->acl = serialize($acl);

                $params = $e->getRouteMatch()->getParams();
                $resource = $params['controller'];
                $privilege = $params['action'];

                $role = $session->roleCode;


                if (!$acl->hasResource($resource) || (!$acl->isAllowed($role, $resource, $privilege))) {

                    /** @var Response $response */
                    $response = $e->getResponse();
                    $response->setStatusCode(403);
                    $response->sendHeaders();

                    // To avoid additional processing
                    // we can attach a listener for Event Route with a high priority
                    $stopCallBack = function ($event) use ($response) {
                        $event->stopPropagation();
                        return $response;
                    };
                    //Attach the "break" as a listener with a high priority
                    $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                    return $response;
                }
            }
        }
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'UserTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserTable($dbAdapter);
                    }
                },
                'UserLoginHistoryTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserLoginHistoryTable($dbAdapter);
                    }
                },
                'FacilitiesTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new FacilitiesTable($dbAdapter);
                    }
                },
                'RoleTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new RoleTable($dbAdapter);
                    }
                },
                'RecencyTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new RecencyTable($dbAdapter);
                    }
                },
                'RiskPopulationsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new RiskPopulationsTable($dbAdapter);
                    }
                },
                'GlobalConfigTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new GlobalConfigTable($dbAdapter);
                    }
                },
                'UserFacilityMapTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new UserFacilityMapTable($dbAdapter);
                    }
                },
                'ProvinceTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new ProvinceTable($dbAdapter);
                    }
                },
                'DistrictTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new DistrictTable($dbAdapter);
                    }
                },
                'CityTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new CityTable($dbAdapter);
                    }
                },
                'QualityCheckTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new QualityCheckTable($dbAdapter);
                    }
                },
                'TempMailTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TempMailTable($dbAdapter);
                    }
                },
                'TestingFacilityTypeTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TestingFacilityTypeTable($dbAdapter);
                    }
                },
                'RecencyChangeTrailsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new RecencyChangeTrailsTable($dbAdapter);
                    }
                },
                'ManageColumnsMapTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new ManageColumnsMapTable($dbAdapter);
                    }
                },
                'SettingsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new SettingsTable($dbAdapter);
                    }
                },
                'SettingsQcSampleTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new SettingsQcSampleTable($dbAdapter);
                    }
                },
                'EventLogTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new EventLogTable($dbAdapter);
                    }
                },
                'ManifestsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new ManifestsTable($dbAdapter);
                    }
                },
                'AuditRecencyTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new AuditRecencyTable($dbAdapter);
                    }
                },
                'ResourcesTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new ResourcesTable($dbAdapter);
                    }
                },
                'AppAcl'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $resourcesTable = $diContainer->get('ResourcesTable');
                        $rolesTable = $diContainer->get('RoleTable');
                        return new Acl($resourcesTable->fetchAllResourceMap(), $rolesTable->fetchRoleAllDetails());
                    }
                },
                'SampleTypesTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new SampleTypesTable($dbAdapter);
                    }
                },
                'SystemAlertsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new SystemAlertsTable($dbAdapter);
                    }
                },
                'TrackApiRequestsTable'  => new class
                {
                    public function __invoke($diContainer)
                    {
                        $dbAdapter = $diContainer->get('Laminas\Db\Adapter\Adapter');
                        return new TrackApiRequestsTable($dbAdapter);
                    }
                },
                //service


                'CommonService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new CommonService($diContainer);
                    }
                },
                'UserService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new UserService($diContainer);
                    }
                },
                'FacilitiesService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new FacilitiesService($diContainer);
                    }
                },
                'RecencyService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new RecencyService($diContainer);
                    }
                },
                'RiskPopulationsService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new RiskPopulationsService($diContainer);
                    }
                },
                'GlobalConfigService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new GlobalConfigService($diContainer);
                    }
                },
                'QualityCheckService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new QualityCheckService($diContainer);
                    }
                },
                'SettingsService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new SettingsService($diContainer);
                    }
                },
                'ProvinceService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new ProvinceService($diContainer);
                    }
                },
                'DistrictService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new DistrictService($diContainer);
                    }
                },
                'CityService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new CityService($diContainer);
                    }
                },
                'ManifestsService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new ManifestsService($diContainer);
                    }
                },
                'RoleService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new RoleService($diContainer);
                    }
                },
                'SampleTypesService' => new class
                {
                    public function __invoke($diContainer)
                    {
                        return new SampleTypesService($diContainer);
                    }
                }

            )
        );
    }
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'GlobalConfig'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalTable = $diContainer->get('GlobalConfigTable');
                        return new GlobalConfig($globalTable);
                    }
                },
                'UserCrossLogin'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userTable = $diContainer->get('UserTable');
                        return new UserCrossLogin($userTable);
                    }
                },
                'CustomConfig'         => new class
                {
                    public function __invoke($diContainer)
                    {
                        $configResult = $diContainer->get('Config');
                        return new CustomConfig($configResult);
                    }
                }
            ),
        );
    }


    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Application\Controller\LoginController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        return new LoginController($userService);
                    }
                },
                'Application\Controller\CaptchaController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new CaptchaController($commonService);
                    }
                },
                'Application\Controller\CommonController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $commonService = $diContainer->get('CommonService');
                        return new CommonController($commonService);
                    }
                },
                'Application\Controller\RecencyController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $settingsService = $diContainer->get('SettingsService');
                        $sampleTypesService = $diContainer->get('SampleTypesService');
                        return new RecencyController($recencyService, $facilitiesService, $globalConfigService, $settingsService, $sampleTypesService);
                    }
                },
                'Application\Controller\UserController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new UserController($userService, $globalConfigService);
                    }
                },
                'Application\Controller\FacilitiesController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new FacilitiesController($facilitiesService, $userService, $globalConfigService);
                    }
                },
                'Application\Controller\GlobalConfigController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new GlobalConfigController($globalConfigService);
                    }
                },
                'Application\Controller\SettingsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $settingsService = $diContainer->get('SettingsService');
                        return new SettingsController($settingsService);
                    }
                },
                'Application\Controller\PrintResultsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        return new PrintResultsController($recencyService);
                    }
                },
                'Application\Controller\CronController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $commonService = $diContainer->get('CommonService');
                        return new CronController($recencyService, $commonService);
                    }
                },
                'Application\Controller\ProvinceController' => new class
                {
                    public function __invoke($diContainer)
                    {

                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $provinceService = $diContainer->get('ProvinceService');
                        return new ProvinceController($provinceService, $globalConfigService);
                    }
                },
                'Application\Controller\DistrictController' => new class
                {
                    public function __invoke($diContainer)
                    {

                        $districtService = $diContainer->get('DistrictService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $provinceService = $diContainer->get('ProvinceService');
                        return new DistrictController($districtService, $provinceService, $globalConfigService);
                    }
                },
                'Application\Controller\CityController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $cityService = $diContainer->get('CityService');
                        $districtService = $diContainer->get('DistrictService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        return new CityController($cityService, $districtService, $globalConfigService);
                    }
                },
                'Application\Controller\QualityCheckController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $qualityCheckService = $diContainer->get('QualityCheckService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $settingsService = $diContainer->get('SettingsService');
                        return new QualityCheckController($qualityCheckService, $facilitiesService, $settingsService);
                    }
                },
                'Application\Controller\ManifestsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $manifestsService = $diContainer->get('ManifestsService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $commonService = $diContainer->get('CommonService');
                        return new ManifestsController($manifestsService, $recencyService, $facilitiesService, $globalConfigService, $commonService);
                    }
                },
                'Application\Controller\IndexController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        return new IndexController($recencyService, $facilitiesService, $globalConfigService);
                    }
                },
                'Application\Controller\VlDataController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $qualityCheckService = $diContainer->get('QualityCheckService');
                        return new VlDataController($recencyService, $facilitiesService, $globalConfigService, $qualityCheckService);
                    }
                },
                'Application\Controller\MonitoringController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $userService = $diContainer->get('UserService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        return new MonitoringController($userService, $globalConfigService, $facilitiesService);
                    }
                },
                'Application\Controller\RolesController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $roleService = $diContainer->get('RoleService');
                        return new RolesController($roleService);
                    }
                },
                'Application\Controller\ReportsController' => new class
                {
                    public function __invoke($diContainer)
                    {
                        $recencyService = $diContainer->get('RecencyService');
                        $facilitiesService = $diContainer->get('FacilitiesService');
                        $globalConfigService = $diContainer->get('GlobalConfigService');
                        $qualityCheckService = $diContainer->get('QualityCheckService');
                        return new ReportsController($recencyService, $facilitiesService, $globalConfigService, $qualityCheckService);
                    }
                },
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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
