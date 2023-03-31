<?php

return [
    'domain' => 'http://recency-web',
    'vlsm' => [
        'domain' => 'http://vlsm/',
        'crosslogin' => true,
        'crosslogin-salt' => 'LP0fznuQV/IVNd2SQ0OfOcJ/4kC5UL6vHKWPILD6Yno='
    ],
    'password' => [
        'salt' => '0This1Is2A3Real4Complex5And6Safe7Salt8With9Some10Dynamic11Stuff12Attched13later'
    ],
    'email' => [
        'host' => 'smtp.gmail.com',
        'config' => [
            'port' => 587,
            'username' => 'zfmailexample@gmail.com',
            'password' => 'mko)(*&^@123',
            'ssl' => 'tls',
            'auth' => 'login'
        ],
    ]
];
