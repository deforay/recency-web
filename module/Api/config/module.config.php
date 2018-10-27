<?php

return array(
     'router' => array(
          'routes' => array(

               'api-login' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/login[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\Login',
                         ),
                    ),
               ),

               'api-facility' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/facility[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\Facility',
                         ),
                    ),
               ),

               'api-recency' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/recency[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\Recency',
                         ),
                    ),
               ),

               'api-risk-populations' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/risk-populations[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RiskPopulations',
                         ),
                    ),
               ),

               'api-global-config' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/global-config[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\GlobalConfig',
                         ),
                    ),
               ),

               'api-province' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/province[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\Province',
                         ),
                    ),
               ),

               'api-district' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/district[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\District',
                         ),
                    ),
               ),

               'api-city' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/city[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\City',
                         ),
                    ),
               ),
               'api-recency-mandatory' => array(
                'type'    => 'segment',
                'options' => array(
                     'route'    => '/api/recency-mandatory[/:id]',
                     'constraints' => array(
                          'id'     => '[0-9]+',
                     ),
                     'defaults' => array(
                          'controller' => 'Api\Controller\RecencyMandatory',
                     ),
                ),
                ),
                'api-quality-check' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/quality-check[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\QualityCheck',
                         ),
                    ),
                    ),

          ),
     ),
     'controllers' => array(
          'invokables' => array(
               'Api\Controller\Login' => 'Api\Controller\LoginController',
               'Api\Controller\Facility' => 'Api\Controller\FacilityController',
               'Api\Controller\Recency' => 'Api\Controller\RecencyController',
               'Api\Controller\RiskPopulations' => 'Api\Controller\RiskPopulationsController',
               'Api\Controller\GlobalConfig' => 'Api\Controller\GlobalConfigController',

               'Api\Controller\Province' => 'Api\Controller\ProvinceController',
               'Api\Controller\District' => 'Api\Controller\DistrictController',
               'Api\Controller\City' => 'Api\Controller\CityController',
               'Api\Controller\RecencyMandatory' => 'Api\Controller\RecencyMandatoryController',
               'Api\Controller\QualityCheck' => 'Api\Controller\QualityCheckController',


          ),
     ),
     'view_manager' => array(
          'strategies' => array(
               'ViewJsonStrategy',
          ),
     ),
);
