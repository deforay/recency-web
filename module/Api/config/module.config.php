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
            
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\Login' => 'Api\Controller\LoginController',

        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);
