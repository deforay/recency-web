<?php

namespace Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemAlerts extends Command
{

    public \Application\Service\RecencyService $recencyService;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->recencyService->systemAlerts();
        return 1;
    }
}
