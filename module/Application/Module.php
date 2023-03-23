<?php

namespace Application;

use Laminas\Session\Container;

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;

// Models
use Application\Model\UserTable;
use Application\Model\UserLoginHistoryTable;
use Application\Model\FacilitiesTable;
use Application\Model\RoleTable;
use Application\Model\RecencyTable;
use Application\Model\RiskPopulationsTable;
use Application\Model\GlobalConfigTable;
use Application\Model\UserFacilityMapTable;
use Application\Model\TempMailTable;
use Application\Model\SettingsTable;
use Application\Model\SettingsQcSampleTable;
use Application\Model\AuditRecencyTable;
use Application\Model\ResourcesTable;

use Application\Model\ProvinceTable;
use Application\Model\DistrictTable;
use Application\Model\CityTable;
use Application\Model\QualityCheckTable;
use Application\Model\TestingFacilityTypeTable;
use Application\Model\RecencyChangeTrailsTable;
use Application\Model\ManageColumnsMapTable;
use Application\Model\EventLogTable;
use Application\Model\ManifestsTable;
use Application\Model\Acl;
// Service

use Application\Service\CommonService;
use Application\Service\UserService;
use Application\Service\FacilitiesService;
use Application\Service\RecencyService;
use Application\Service\RiskPopulationsService;
use Application\Service\GlobalConfigService;
use Application\Service\QualityCheckService;
use Application\Service\SettingsService;
use Application\Service\ProvinceService;
use Application\Service\DistrictService;
use Application\Service\CityService;
use Application\Service\ManifestsService;
use Application\Service\RoleService;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        /** @var $application \Laminas\Mvc\Application */
        $application = $e->getApplication();

        $eventManager        = $application->getEventManager();

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //no need to call presetter if request is from CLI
        if (php_sapi_name() != 'cli') {
            $eventManager->attach('dispatch', array($this, 'preSetter'), 100);
        }
    }



    public function preSetter(MvcEvent $e)
    {

        /** @var $application \Laminas\Mvc\Application */
        $application = $e->getApplication();
        /** @var \Laminas\Http\Request $request */
        $request = $e->getRequest();

        if (
            !$request->isXmlHttpRequest()
            && ($e->getRouteMatch()->getParam('controller') != 'Application\Controller\Login') &&
            ($e->getRouteMatch()->getParam('controller') != 'Application\Controller\Captcha')
        ) {


            $session = new Container('credo');
            if (empty($session) || !isset($session->userId) || empty($session->userId)) {
                $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                /** @var \Laminas\Http\Response $response */
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
                $sm = $application->getServiceManager();
                $viewModel = $application->getMvcEvent()->getViewModel();
                $acl = $sm->get('AppAcl');
                $viewModel->acl = $acl;
                $session->acl = serialize($acl);

                $params = $e->getRouteMatch()->getParams();
                $resource = $params['controller'];
                $privilege = $params['action'];

                $role = $session->roleCode;


                if (!$acl->hasResource($resource) || (!$acl->isAllowed($role, $resource, $privilege))) {
                    
                    /** @var \Laminas\Http\Response $response */
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
                'UserTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new UserTable($dbAdapter);
                    return $table;
                },
                'UserLoginHistoryTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new UserLoginHistoryTable($dbAdapter);
                    return $table;
                },
                'FacilitiesTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new FacilitiesTable($dbAdapter);
                    return $table;
                },
                'RoleTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new RoleTable($dbAdapter);
                    return $table;
                },
                'RecencyTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new RecencyTable($dbAdapter);
                    return $table;
                },
                'RiskPopulationsTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new RiskPopulationsTable($dbAdapter);
                    return $table;
                },
                'GlobalConfigTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new GlobalConfigTable($dbAdapter);
                    return $table;
                },
                'UserFacilityMapTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new UserFacilityMapTable($dbAdapter);
                    return $table;
                },

                'ProvinceTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new ProvinceTable($dbAdapter);
                    return $table;
                },

                'DistrictTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new DistrictTable($dbAdapter);
                    return $table;
                },

                'CityTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new CityTable($dbAdapter);
                    return $table;
                },

                'QualityCheckTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new QualityCheckTable($dbAdapter);
                    return $table;
                },
                'TempMailTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new TempMailTable($dbAdapter);
                    return $table;
                },
                'TestingFacilityTypeTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new TestingFacilityTypeTable($dbAdapter);
                    return $table;
                },
                'RecencyChangeTrailsTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new RecencyChangeTrailsTable($dbAdapter);
                    return $table;
                },
                'ManageColumnsMapTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new ManageColumnsMapTable($dbAdapter);
                    return $table;
                },
                'SettingsTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new SettingsTable($dbAdapter);
                    return $table;
                },
                'SettingsQcSampleTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new SettingsQcSampleTable($dbAdapter);
                    return $table;
                },
                'EventLogTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new EventLogTable($dbAdapter);
                    return $table;
                },
                'ManifestsTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new ManifestsTable($dbAdapter);
                    return $table;
                },
                'AuditRecencyTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new AuditRecencyTable($dbAdapter);
                    return $table;
                },
                'ResourcesTable' => function ($sm) {
                    $dbAdapter = $sm->get('Laminas\Db\Adapter\Adapter');
                    $table = new ResourcesTable($dbAdapter);
                    return $table;
                },
                'AppAcl' => function ($sm) {
                    $resourcesTable = $sm->get('ResourcesTable');
                    $rolesTable = $sm->get('RoleTable');
                    return new Acl($resourcesTable->fetchAllResourceMap(), $rolesTable->fetchRoleAllDetails());
                },

                //service

                'CommonService' => function ($sm) {
                    return new CommonService($sm);
                },

                'UserService' => function ($sm) {
                    return new UserService($sm);
                },
                'FacilitiesService' => function ($sm) {
                    return new FacilitiesService($sm);
                },
                'RecencyService' => function ($sm) {
                    return new RecencyService($sm);
                },
                'RiskPopulationsService' => function ($sm) {
                    return new RiskPopulationsService($sm);
                },
                'GlobalConfigService' => function ($sm) {
                    return new GlobalConfigService($sm);
                },

                'QualityCheckService' => function ($sm) {
                    return new QualityCheckService($sm);
                },
                'SettingsService' => function ($sm) {
                    return new SettingsService($sm);
                },
                'ProvinceService' => function ($sm) {
                    return new ProvinceService($sm);
                },
                'DistrictService' => function ($sm) {
                    return new DistrictService($sm);
                },
                'CityService' => function ($sm) {
                    return new CityService($sm);
                },
                'ManifestsService' => function ($sm) {
                    return new ManifestsService($sm);
                },
                'RoleService' => function ($sm) {
                    return new RoleService($sm);
                },

            )
        );
    }
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'GlobalConfig'          => function ($sm) {
                    $globalTable = $sm->getServiceLocator()->get('GlobalConfigTable');
                    return new \Application\View\Helper\GlobalConfig($globalTable);
                },
                'UserCrossLogin' => function ($sm) {
                    $userTable = $sm->getServiceLocator()->get('UserTable');
                    return new \Application\View\Helper\UserCrossLogin($userTable);
                }
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Application\Controller\Login' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    return new \Application\Controller\LoginController($userService);
                },
                'Application\Controller\Common' => function ($sm) {
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Application\Controller\CommonController($commonService);
                },
                'Application\Controller\Captcha' => function ($sm) {
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Application\Controller\CaptchaController($commonService);
                },
                'Application\Controller\User' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Application\Controller\UserController($userService, $globalConfigService);
                },
                'Application\Controller\Facilities' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Application\Controller\FacilitiesController($facilitiesService, $userService, $globalConfigService);
                },
                'Application\Controller\GlobalConfig' => function ($sm) {
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Application\Controller\GlobalConfigController($globalConfigService);
                },
                'Application\Controller\Settings' => function ($sm) {
                    $settingsService = $sm->getServiceLocator()->get('SettingsService');
                    return new \Application\Controller\SettingsController($settingsService);
                },
                'Application\Controller\PrintResults' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    return new \Application\Controller\PrintResultsController($recencyService);
                },
                'Application\Controller\Cron' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Application\Controller\CronController($recencyService, $commonService);
                },
                'Application\Controller\Province' => function ($sm) {

                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $provinceService = $sm->getServiceLocator()->get('ProvinceService');
                    return new \Application\Controller\ProvinceController($provinceService, $globalConfigService);
                },
                'Application\Controller\District' => function ($sm) {

                    $districtService = $sm->getServiceLocator()->get('DistrictService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $provinceService = $sm->getServiceLocator()->get('ProvinceService');
                    return new \Application\Controller\DistrictController($districtService, $provinceService, $globalConfigService);
                },
                'Application\Controller\City' => function ($sm) {
                    $cityService = $sm->getServiceLocator()->get('CityService');
                    $districtService = $sm->getServiceLocator()->get('DistrictService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Application\Controller\CityController($cityService, $districtService, $globalConfigService);
                },
                'Application\Controller\Recency' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    $settingsService = $sm->getServiceLocator()->get('SettingsService');
                    return new \Application\Controller\RecencyController($recencyService, $facilitiesService, $globalConfigService, $settingsService);
                },
                'Application\Controller\QualityCheck' => function ($sm) {
                    $qualityCheckService = $sm->getServiceLocator()->get('QualityCheckService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    $settingsService = $sm->getServiceLocator()->get('SettingsService');
                    return new \Application\Controller\QualityCheckController($qualityCheckService, $facilitiesService, $settingsService);
                },
                'Application\Controller\Manifests' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    $manifestsService = $sm->getServiceLocator()->get('ManifestsService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $commonService = $sm->getServiceLocator()->get('CommonService');
                    return new \Application\Controller\ManifestsController($manifestsService, $recencyService, $facilitiesService, $globalConfigService, $commonService);
                },
                'Application\Controller\Index' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    return new \Application\Controller\IndexController($recencyService, $facilitiesService, $globalConfigService);
                },
                'Application\Controller\VlData' => function ($sm) {
                    $recencyService = $sm->getServiceLocator()->get('RecencyService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    $facilitiesService = $sm->getServiceLocator()->get('FacilitiesService');
                    $qualityCheckService = $sm->getServiceLocator()->get('QualityCheckService');
                    return new \Application\Controller\VlDataController($recencyService, $facilitiesService, $globalConfigService, $qualityCheckService);
                },
                'Application\Controller\Monitoring' => function ($sm) {
                    $userService = $sm->getServiceLocator()->get('UserService');
                    $globalConfigService = $sm->getServiceLocator()->get('GlobalConfigService');
                    return new \Application\Controller\MonitoringController($userService, $globalConfigService);
                },
                'Application\Controller\Roles' => function ($sm) {
                    $roleService = $sm->getServiceLocator()->get('RoleService');
                    return new \Application\Controller\RolesController($roleService);
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
