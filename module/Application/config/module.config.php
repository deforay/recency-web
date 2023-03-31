<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
     'router' => array(
          'routes' => array(
               'home' => array(
                    'type' => 'Literal',
                    'options' => array(
                         'route'    => '/',
                         'defaults' => array(
                              'controller' => 'Application\Controller\IndexController',
                              'action'     => 'index',
                         ),
                    ),
               ),
               'analysis-dashboard' => array(
                    'type'    => 'Literal',
                    'options' => array(
                         'route'    => '/analysis-dashboard',
                         'defaults' => array(
                              'controller' => 'Application\Controller\IndexController',
                              'action' => 'analysis-dashboard',
                         ),
                    ),
               ),
               'quality-control-dashboard' => array(
                    'type'    => 'Literal',
                    'options' => array(
                         'route'    => '/quality-control-dashboard',
                         'defaults' => array(
                              'controller' => 'Application\Controller\IndexController',
                              'action' => 'quality-control-dashboard',
                         ),
                    ),
               ),
               'export-recency-data' => array(
                    'type'    => 'Literal',
                    'options' => array(
                         'route'    => '/export-recency-data',
                         'defaults' => array(
                              'controller' => 'Application\Controller\IndexController',
                              'action' => 'export-recency-data',
                         ),
                    ),
               ),
               'dashboard' => array(
                    'type'    => 'Literal',
                    'options' => array(
                         'route'    => '/set-sample-first-chart',
                         'defaults' => array(
                              'controller' => 'Application\Controller\IndexController',
                              'action' => 'set-sample-first-chart',
                         ),
                    ),
               ),
               'login' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route'    => '/login[/:action]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\LoginController',
                              'action' => 'index',
                         ),
                    ),
               ),

               'logout' => array(
                    'type' => 'Literal',
                    'options' => array(
                         'route'    => '/logout',
                         'defaults' => array(
                              'controller' => 'Application\Controller\Login',
                              'action'     => 'logout',
                         ),
                    ),
               ),

               'common' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/common[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\CommonController',
                              'action' => 'index',
                         ),
                    ),
               ),

               'facilities' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/facilities[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\FacilitiesController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'user' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/user[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\UserController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'recency' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/recency[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\RecencyController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'global-config' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/global-config[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\GlobalConfigController',
                              'action' => 'index',
                         ),
                    ),
               ),

               'quality-check' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/quality-check[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\QualityCheckController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'captcha' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/captcha[/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\CaptchaController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'checkcaptcha' => array(
                    'type' => 'literal',
                    'options' => array(
                         'route' => '/checkcaptcha',
                         'defaults' => array(
                              'controller' => 'Application\Controller\CaptchaController',
                              'action' => 'check-captcha',
                         ),
                    ),
               ),
               'vl-data' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/vl-data[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\VlDataController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'settings' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/settings[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\SettingsController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'province' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/province[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\ProvinceController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'district' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/district[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\DistrictController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'city' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/city[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\CityController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'print-results' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/print-results[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\PrintResultsController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'manifests' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/manifests[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\ManifestsController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'monitoring' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/monitoring[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\MonitoringController',
                              'action' => 'index',
                         ),
                    ),
               ),
               'roles' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/roles[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Application\Controller\RolesController',
                              'action' => 'index',
                         ),
                    ),
               ),
               // The following is a route to simplify getting started creating
               // new controllers and actions without needing to create a new
               // module. Simply drop new controllers in, and you can access them
               // using the path /application/:controller/:action
               'application' => array(
                    'type'    => 'Literal',
                    'options' => array(
                         'route'    => '/application',
                         'defaults' => array(
                              '__NAMESPACE__' => 'Application\Controller',
                              'controller'    => 'Index',
                              'action'        => 'index',
                         ),
                    ),
                    'may_terminate' => true,
                    'child_routes' => array(
                         'default' => array(
                              'type'    => 'Segment',
                              'options' => array(
                                   'route'    => '/[:controller[/:action]]',
                                   'constraints' => array(
                                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                   ),
                                   'defaults' => array(),
                              ),
                         ),
                    ),
               ),
          ),
     ),
     'service_manager' => array(
          'abstract_factories' => array(
               'Laminas\Cache\Service\StorageCacheAbstractServiceFactory',
               'Laminas\Log\LoggerAbstractServiceFactory',
          ),
          'factories' => [
               Application\Command\VlsmSendRequests::class => Application\Command\VlsmSendRequestsFactory::class,
               Application\Command\SendMail::class => Application\Command\SendMailFactory::class,
          ],
          'aliases' => array(
               'translator' => 'MvcTranslator',
          ),
     ),
     'translator' => array(
          'locale' => 'en_US',
          'translation_file_patterns' => array(
               array(
                    'type'     => 'gettext',
                    'base_dir' => __DIR__ . '/../language',
                    'pattern'  => '%s.mo',
               ),
          ),
     ),
     'view_manager' => array(
          'display_not_found_reason' => true,
          'display_exceptions'       => true,
          'doctype'                  => 'HTML5',
          'not_found_template'       => 'error/404',
          'exception_template'       => 'error/index',
          'template_map' => array(
               'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
               'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
               'error/404'               => __DIR__ . '/../view/error/404.phtml',
               'error/index'             => __DIR__ . '/../view/error/index.phtml',
          ),
          'template_path_stack' => array(
               __DIR__ . '/../view',
          ),
     ),
     'laminas-cli' => [
          'commands' => [
               'send-mail' => Application\Command\SendMail::class,
               'vlsm-send-requests' => \Application\Command\VlsmSendRequests::class,
          ],
     ],
     // Placeholder for console routes
     'console' => array(
          'router' => array(
               'routes' => array(
                    'mail-console-route' => array(
                         'type'    => 'simple',
                         'options' => array(
                              'route'    => 'send-mail',
                              'defaults' => array(
                                   'controller' => 'Application\Controller\CronController',
                                   'action' => 'send-mail'
                              ),
                         ),
                    ),
                    'update-outcome' => array(
                         'type'    => 'simple',
                         'options' => array(
                              'route'    => 'update-outcome',
                              'defaults' => array(
                                   'controller' => 'Application\Controller\CronController',
                                   'action' => 'update-outcome'
                              ),
                         ),
                    ),
                    'vlsm-sync' => array(
                         'type'    => 'simple',
                         'options' => array(
                              'route'    => 'vlsm-sync',
                              'defaults' => array(
                                   'controller' => 'Application\Controller\CronController',
                                   'action' => 'vlsm-sync'
                              ),
                         ),
                    ),
                    'vlsm-send-requests' => array(
                         'type'    => 'simple',
                         'options' => array(
                              'route'    => 'vlsm-send-requests',
                              'defaults' => array(
                                   'controller' => 'Application\Controller\CronController',
                                   'action' => 'vlsm-send-requests'
                              ),
                         ),
                    ),
               ),
          ),
     ),
);
