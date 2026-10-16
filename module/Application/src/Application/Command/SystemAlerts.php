<?php

namespace Application\Command;

use Application\Service\RecencyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemAlerts extends Command
{

    public RecencyService $recencyService;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Raise an alert for each sample waiting over 30 days for a viral load result');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recencyService->systemAlerts();
        return Command::SUCCESS;
    }
}
