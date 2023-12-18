<?php

namespace Application\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Command\VlsmSampleStatus;

class VlsmSampleStatusFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $recencyService = $container->get('RecencyService');

        return new VlsmSampleStatus($recencyService);
    }
}
