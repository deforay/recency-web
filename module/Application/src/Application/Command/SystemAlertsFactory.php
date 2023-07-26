<?php

namespace Application\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Command\SystemAlerts;

class SystemAlertsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $recencyService = $container->get('RecencyService');

        return new SystemAlerts($recencyService);
    }
}

