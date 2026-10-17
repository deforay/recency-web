<?php

/**
 * Defaults only. This file is tracked and public: never put a real value here.
 *
 * Each install sets its own values in config/autoload/custom.local.php, which
 * git ignores and which is loaded after this file, so any key it sets wins.
 * Copy custom.local.php.dist to start one.
 */

return [
    'domain' => '',
    'vlsm' => [
        'domain' => '',
        // Run the scheduled InteLIS jobs (send requests, fetch results and status)
        'sync' => false,
        'crosslogin' => false,
        'crosslogin-salt' => ''
    ],
    'password' => [
        'salt' => ''
    ],
    'email' => [
        'host' => '',
        'config' => [
            'port' => 587,
            'username' => '',
            'password' => '',
            'ssl' => 'tls',
            'auth' => 'login'
        ],
    ],
    'authToken' => ''
];
