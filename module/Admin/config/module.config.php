<?php
/**
* Zend Framework (http://framework.zend.com/)
*
* @link      http://github.com/zendframework/ZendSkeletonAdmin for the canonical source repository
* @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
* @license   http://framework.zend.com/license/new-bsd New BSD License
*/

return array(
     'router' => array(
          'routes' => array(
               'admin-home' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/admin[/]',
                         'defaults' => array(
                              'controller' => 'Admin\Controller\Index',
                              'action' => 'index',
                         ),
                    ),
               ),


               'admin-login' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route'    => '/admin/login[/:action]',
                         'defaults' => array(
                              'controller' => 'Admin\Controller\Login',
                              'action' => 'index',
                         ),
                    ),
               ),
               'common' => array(
                    'type' => 'segment',
                    'options' => array(
                         'route' => '/admin/common[/:action][/][:id]',
                         'defaults' => array(
                              'controller' => 'Admin\Controller\Common',
                              'action' => 'index',
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
               'Admin\Controller\Index' => 'Admin\Controller\IndexController',
               'Admin\Controller\Common' => 'Admin\Controller\CommonController',


               'Admin\Controller\Login' => 'Admin\Controller\LoginController',

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
               'admin/index/index' => __DIR__ . '/../view/admin/index/index.phtml',
               'error/404'               => __DIR__ . '/../view/error/404.phtml',
               'error/index'             => __DIR__ . '/../view/error/index.phtml',
          ),
          'template_path_stack' => array(
               __DIR__ . '/../view',
          ),
     ),

     // Placeholder for console routes
     'console' => array(
          'router' => array(
               'routes' => array(
               ),
          ),
     ),
);
