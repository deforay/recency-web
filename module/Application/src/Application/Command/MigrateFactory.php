<?php

namespace Application\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;

class MigrateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var Adapter $adapter */
        $adapter = $container->get('Laminas\Db\Adapter\Adapter');
        // Connect only when the command runs, so listing commands needs no database
        $connect = static fn(): \PDO => $adapter->getDriver()->getConnection()->connect()->getResource();

        return new Migrate($connect, dirname(__DIR__, 5) . '/migrations');
    }
}
