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
               'api-password' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/update-password[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\UpdatePassword',
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

               'api-recency-result-with-vl' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/recency-result-with-vl[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyResultWithVl',
                         ),
                    ),
               ),
               
               'api-pending-vl-result' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/pending-vl-result[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\PendingVlResult',
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
               'api-recency-hide' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/recency-hide[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyHide',
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
                'api-tat-report' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/tat-report[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TatReport',
                         ),
                    ),
                ),
                'api-technical-support' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/technical-support[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TechnicalSupport',
                         ),
                    ),
                ),
                'api-vl-test-result' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/vl-test-result[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\VlTestResult',
                         ),
                    ),
                ),
                'api-test-kit-info' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/test-kit-info[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TestKitInfo',
                         ),
                    ),
                ),

                'api-sample' => array(
                    'type'    => 'segment',
                    'options' => array(
                         'route'    => '/api/sample[/:id]',
                         'constraints' => array(
                              'id'     => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\SampleData',
                         ),
                    ),
               ),

          ),
     ),
     'controllers' => array(
          'invokables' => array(
               'Api\Controller\Login'                  => 'Api\Controller\LoginController',
               'Api\Controller\UpdatePassword'         => 'Api\Controller\UpdatePasswordController',
               'Api\Controller\Facility'               => 'Api\Controller\FacilityController',
               'Api\Controller\Recency'                => 'Api\Controller\RecencyController',
               'Api\Controller\RecencyResultWithVl'    => 'Api\Controller\RecencyResultWithVlController',
               'Api\Controller\PendingVlResult'        => 'Api\Controller\PendingVlResultController',
               'Api\Controller\RiskPopulations'        => 'Api\Controller\RiskPopulationsController',
               'Api\Controller\GlobalConfig'           => 'Api\Controller\GlobalConfigController',

               'Api\Controller\Province'               => 'Api\Controller\ProvinceController',
               'Api\Controller\District'               => 'Api\Controller\DistrictController',
               'Api\Controller\City'                   => 'Api\Controller\CityController',
               'Api\Controller\RecencyMandatory'       => 'Api\Controller\RecencyMandatoryController',
               'Api\Controller\RecencyHide'            => 'Api\Controller\RecencyHideController',
               'Api\Controller\QualityCheck'           => 'Api\Controller\QualityCheckController',
               'Api\Controller\TatReport'              => 'Api\Controller\TatReportController',
               'Api\Controller\TechnicalSupport'       => 'Api\Controller\TechnicalSupportController',
               'Api\Controller\VlTestResult'           => 'Api\Controller\VlTestResultController',
               'Api\Controller\TestKitInfo'            => 'Api\Controller\TestKitInfoController',
               'Api\Controller\SampleData'             => 'Api\Controller\SampleDataController',
               
               
          ),
     ),
     'view_manager' => array(
          'strategies' => array(
               'ViewJsonStrategy',
          ),
     ),
);
