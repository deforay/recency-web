<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
$config = new \Laminas\Config\Reader\Ini();
$configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');

return array(
    'db' => array(
        'driver'         => 'Pdo',
        'dsn'            => 'mysql:dbname=recency_app;host=localhost',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
        'adapters'=>array(
            'db1' => array(
                'driver'  => 'Pdo',
                'dsn'     => 'mysql:dbname=vlrbc;host=localhost',      
                'driver_options'  => array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
                ),
            ),
        )
    ),
    'module_layouts' => array(
        'Application' => 'layout/layout',
    ),
    'service_manager' => array(
        'factories' => array(
            'Laminas\Db\Adapter\Adapter'
                    => 'Laminas\Db\Adapter\AdapterServiceFactory',
        ),
        // to allow other adapter to be called by
        // $sm->get('db1') or $sm->get('db2') based on the adapters config.
        'abstract_factories' => array(
            'Laminas\Db\Adapter\AdapterAbstractServiceFactory',
        ),
    ),
);
