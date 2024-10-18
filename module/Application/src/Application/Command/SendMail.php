<?php

namespace Application\Command;

use Application\Service\CommonService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendMail extends Command
{

    public CommonService  $commonService;

    public function __construct($commonService)
    {
        $this->commonService = $commonService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Call the CommonService's method to send the mail
            $this->commonService->sendTempMail();
            // Return success status code
            return Command::SUCCESS;
        } catch (\Exception $e) {
            // Log error and return failure status code
            $output->writeln('<error>Error sending mail: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
