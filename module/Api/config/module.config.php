<?php

return array(
     'router' => array(
          'routes' => array(

               'api-login' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/login[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\LoginController',
                         ),
                    ),
               ),
               'api-password' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/update-password[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\UpdatePasswordController',
                         ),
                    ),
               ),

               'api-facility' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/facility[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\FacilityController',
                         ),
                    ),
               ),

               'api-recency' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/recency[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyController',
                         ),
                    ),
               ),

               'api-recency-result-with-vl' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/recency-result-with-vl[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyResultWithVlController',
                         ),
                    ),
               ),

               'api-pending-vl-result' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/pending-vl-result[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\PendingVlResultController',
                         ),
                    ),
               ),

               'api-risk-populations' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/risk-populations[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RiskPopulationsController',
                         ),
                    ),
               ),

               'api-global-config' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/global-config[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\GlobalConfigController',
                         ),
                    ),
               ),

               'api-province' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/province[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\ProvinceController',
                         ),
                    ),
               ),

               'api-district' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/district[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\DistrictController',
                         ),
                    ),
               ),

               'api-city' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/city[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\CityController',
                         ),
                    ),
               ),
               'api-recency-mandatory' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/recency-mandatory[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyMandatoryController',
                         ),
                    ),
               ),
               'api-recency-sampleid' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/recency-sampleid[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencySampleidController',
                         ),
                    ),
               ),
               'api-recency-hide' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/recency-hide[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\RecencyHideController',
                         ),
                    ),
               ),
               'api-quality-check' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/quality-check[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\QualityCheckController',
                         ),
                    ),
               ),
               'api-tat-report' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/tat-report[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TatReportController',
                         ),
                    ),
               ),
               'api-technical-support' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/technical-support[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TechnicalSupportController',
                         ),
                    ),
               ),
               'api-vl-test-result' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/vl-test-result[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\VlTestResultController',
                         ),
                    ),
               ),
               'api-test-kit-info' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/test-kit-info[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\TestKitInfoController',
                         ),
                    ),
               ),

               'api-sample' => array(
                    'type'  => 'segment',
                    'options' => array(
                         'route'  => '/api/sample[/:id]',
                         'constraints' => array(
                              'id'   => '[0-9]+',
                         ),
                         'defaults' => array(
                              'controller' => 'Api\Controller\SampleDataController',
                         ),
                    ),
               ),

          ),
     ),
     'view_manager' => array(
          'strategies' => array(
               'ViewJsonStrategy',
          ),
     ),
);
