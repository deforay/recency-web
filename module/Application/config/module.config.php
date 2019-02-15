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
                        'type' => 'Zend\Mvc\Router\Http\Literal',
                        'options' => array(
                              'route'    => '/',
                              'defaults' => array(
                              'controller' => 'Application\Controller\Index',
                              'action'     => 'index',
                              ),
                        ),
                  ),

                  'login' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route'    => '/login[/:action]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\Login',
                                    'action' => 'index',
                              ),
                        ),
                  ),

                  'logout' => array(
                        'type' => 'Zend\Mvc\Router\Http\Literal',
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
                              'controller' => 'Application\Controller\Common',
                              'action' => 'index',
                        ),
                        ),
                  ),

                  'facilities' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route' => '/facilities[/:action][/][:id]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\Facilities',
                                    'action' => 'index',
                              ),
                        ),
                  ),
                  'user' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route' => '/user[/:action][/][:id]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\User',
                                    'action' => 'index',
                              ),
                        ),
                  ),
                  'recency' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route' => '/recency[/:action][/][:id]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\Recency',
                                    'action' => 'index',
                              ),
                        ),
                  ),
                  'global-config' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route' => '/global-config[/:action][/][:id]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\GlobalConfig',
                                    'action' => 'index',
                              ),
                        ),
                  ),

                  'quality-check' => array(
                       'type' => 'segment',
                       'options' => array(
                            'route' => '/quality-check[/:action][/][:id]',
                            'defaults' => array(
                                 'controller' => 'Application\Controller\QualityCheck',
                                 'action' => 'index',
                            ),
                       ),
                  ),
                  'captcha' => array(
                        'type' => 'segment',
                        'options' => array(
                             'route' => '/captcha[/][:id]',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Captcha',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'checkcaptcha' => array(
                        'type' => 'literal',
                        'options' => array(
                            'route' => '/checkcaptcha',
                            'defaults' => array(
                                'controller' => 'Application\Controller\Captcha',
                                'action' => 'check-captcha',
                            ),
                        ),
                    ),
                    'vl-data' => array(
                        'type' => 'segment',
                        'options' => array(
                              'route' => '/vl-data[/:action][/][:id]',
                              'defaults' => array(
                                    'controller' => 'Application\Controller\VlData',
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
                                   'defaults' => array(
                                   ),
                              ),
                         ),
                    ),
               ),
          ),
     ),
     'service_manager' => array(
          'abstract_factories' => array(
               'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
               'Zend\Log\LoggerAbstractServiceFactory',
          ),
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


     'controllers' => array(
          'invokables' => array(

               'Application\Controller\Index' => 'Application\Controller\IndexController',
               'Application\Controller\Common' => 'Application\Controller\CommonController',
               'Application\Controller\Login' => 'Application\Controller\LoginController',
               'Application\Controller\Facilities' => 'Application\Controller\FacilitiesController',
               'Application\Controller\User' => 'Application\Controller\UserController',
               'Application\Controller\Recency' => 'Application\Controller\RecencyController',
               'Application\Controller\GlobalConfig' => 'Application\Controller\GlobalConfigController',
               'Application\Controller\QualityCheck' => 'Application\Controller\QualityCheckController',
               'Application\Controller\Captcha' => 'Application\Controller\CaptchaController',
               'Application\Controller\VlData' => 'Application\Controller\VlDataController',
               'Application\Controller\Cron' => 'Application\Controller\CronController',
          ),
     ),
     'controller_plugins' => array(
          'invokables' => array(
               'HasParams' => 'Application\Controller\Plugin\HasParams'
          )
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
     'view_helpers' => array(
          'invokables'=> array(
               'category_helper' => 'Application\View\Helper\CategoryHelper',
          )
     ),
     // Placeholder for console routes
     'console' => array(
      'router' => array(
          'routes' => array(
              'mail-console-route' => array(
                  'type'    => 'simple',
                  'options' => array(
                      'route'    => 'send-mail',
                      'defaults' => array(
                          'controller' => 'Application\Controller\Cron',
                          'action' => 'send-mail'
                      ),
                  ),
              ),
              'update-outcome' => array(
                  'type'    => 'simple',
                  'options' => array(
                      'route'    => 'update-outcome',
                      'defaults' => array(
                          'controller' => 'Application\Controller\Cron',
                          'action' => 'update-outcome'
                      ),
                  ),
              ),
              'vlsm-sync' => array(
                  'type'    => 'simple',
                  'options' => array(
                      'route'    => 'vlsm-sync',
                      'defaults' => array(
                          'controller' => 'Application\Controller\Cron',
                          'action' => 'vlsm-sync'
                      ),
                  ),
              ),
            ),
      ),
),
);
