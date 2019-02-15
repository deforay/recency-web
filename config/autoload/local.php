<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in ZendSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */

$config = new \Zend\Config\Reader\Ini();
$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');

$env = getenv('APP_ENV') ?: 'production';


if($env == 'development'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );
    $local['db']['adapters']['db1'] = array(
        'username' => 'root',
        'password' => 'zaq12345',
    );

}



if($env == 'testing'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );

                $local['db']['adapters']['db1'] = array(
        'username' => 'root',
                    'password' => 'zaq12345',
    );
}



if($env == 'production'){
    $local['db'] = array(
                    'username' => 'root',
                    'password' => 'zaq12345',
                );

                $local['db']['adapters']['db1'] = array(
        'username' => 'root',
        'password' => 'zaq12345',
    );
}


return $local;
