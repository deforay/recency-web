<?php
namespace Application;

use Zend\Session\Container;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

// Models
use Application\Model\UserTable;
use Application\Model\FacilitiesTable;
use Application\Model\RoleTable;
use Application\Model\RecencyTable;
use Application\Model\RiskPopulationsTable;
use Application\Model\GlobalConfigTable;
use Application\Model\UserFacilityMapTable;
use Application\Model\TempMailTable;

use Application\Model\ProvinceTable;
use Application\Model\DistrictTable;
use Application\Model\CityTable;
use Application\Model\QualityCheckTable;
use Application\Model\TestingFacilityTypeTable;
use Application\Model\ManageColumnsMapTable;

// Service

use Application\Service\CommonService;
use Application\Service\UserService;
use Application\Service\FacilitiesService;
use Application\Service\RecencyService;
use Application\Service\RiskPopulationsService;
use Application\Service\GlobalConfigService;
use Application\Service\QualityCheckService;

class Module{
     public function onBootstrap(MvcEvent $e){
          $eventManager        = $e->getApplication()->getEventManager();
          $moduleRouteListener = new ModuleRouteListener();
          $moduleRouteListener->attach($eventManager);

          //no need to call presetter if request is from CLI
          if (php_sapi_name() != 'cli') {
               $eventManager->attach('dispatch', array($this, 'preSetter'), 100);
               $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'dispatchError'), -999);
          }
     }

     public function dispatchError(MvcEvent $event) {
          $error = $event->getError();
          $baseModel = new ViewModel();
          $baseModel->setTemplate('layout/layout');
     }

     public function preSetter(MvcEvent $e) {
        if (($e->getRouteMatch()->getParam('controller') != 'Application\Controller\Login') && ($e->getRouteMatch()->getParam('controller') != 'Application\Controller\Captcha')) {
            $tempName=explode('Controller',$e->getRouteMatch()->getParam('controller'));
            if(substr($tempName[0], 0, -1) == 'Application'){
                $session = new Container('credo');
                if (!isset($session->userId) || $session->userId == "") {
                        $url = $e->getRouter()->assemble(array(), array('name' => 'login'));
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', $url);
                        $response->setStatusCode(302);
                        $response->sendHeaders();
                        // To avoid additional processing
                        // we can attach a listener for Event Route with a high priority
                        $stopCallBack = function($event) use ($response) {
                            $event->stopPropagation();
                            return $response;
                        };
                        //Attach the "break" as a listener with a high priority
                        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $stopCallBack, -10000);
                        return $response;
                    }
            }else{
                if ($e->getRequest()->isXmlHttpRequest()) {
                    return;
                }
            }
        }
    }

     public function getServiceConfig() {
          return array(
               'factories' => array(



                    'UserTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new UserTable($dbAdapter);
                        return $table;
                    },
                    'FacilitiesTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new FacilitiesTable($dbAdapter);
                        return $table;
                    },
                    'RoleTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new RoleTable($dbAdapter);
                        return $table;
                    },
                    'RecencyTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new RecencyTable($dbAdapter);
                        return $table;
                    },
                    'RiskPopulationsTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new RiskPopulationsTable($dbAdapter);
                        return $table;
                    },
                    'GlobalConfigTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new GlobalConfigTable($dbAdapter);
                        return $table;
                    },
                    'UserFacilityMapTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new UserFacilityMapTable($dbAdapter);
                        return $table;
                    },

                    'ProvinceTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new ProvinceTable($dbAdapter);
                        return $table;
                    },

                    'DistrictTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new DistrictTable($dbAdapter);
                        return $table;
                    },

                    'CityTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new CityTable($dbAdapter);
                        return $table;
                    },

                    'QualityCheckTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new QualityCheckTable($dbAdapter);
                        return $table;
                    },
                    'TempMailTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new TempMailTable($dbAdapter);
                        return $table;
                    },
                    'TestingFacilityTypeTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new TestingFacilityTypeTable($dbAdapter);
                        return $table;
                    },
                    'ManageColumnsMapTable' => function($sm) {
                        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new ManageColumnsMapTable($dbAdapter);
                        return $table;
                    },
                    
                    


                    //service

                    'CommonService' => function($sm) {
                         return new CommonService($sm);
                    },

                    'UserService' => function($sm) {
                    return new UserService($sm);
                    },
                    'FacilitiesService' => function($sm) {
                        return new FacilitiesService($sm);
                    },
                    'RecencyService' => function($sm) {
                        return new RecencyService($sm);
                    },
                    'RiskPopulationsService' => function($sm) {
                        return new RiskPopulationsService($sm);
                    },
                    'GlobalConfigService' => function($sm) {
                        return new GlobalConfigService($sm);
                    },

                    'QualityCheckService' => function($sm) {
                         return new QualityCheckService($sm);
               },

               )
          );
     }

     public function getConfig(){
          return include __DIR__ . '/config/module.config.php';
     }

     public function getAutoloaderConfig(){
          return array(
               'Zend\Loader\StandardAutoloader' => array(
                    'namespaces' => array(
                         __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    ),
               ),
          );
     }


}
