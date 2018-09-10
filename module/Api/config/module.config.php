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
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\Login' => 'Api\Controller\LoginController',
            'Api\Controller\Facility' => 'Api\Controller\FacilityController',
            'Api\Controller\Recency' => 'Api\Controller\RecencyController',
            'Api\Controller\RiskPopulations' => 'Api\Controller\RiskPopulationsController',

        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);
