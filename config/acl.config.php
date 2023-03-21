<?php
return array(
    'sss' => array(
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'allow',
        ),
    ),
    'manager' => array(
        'Application\\Controller\\Roles' => array(
            'index' => 'allow',
            'add' => 'deny',
        ),
    ),
);
