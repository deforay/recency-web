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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->commonService->sendTempMail();
        return 1;
    }
}
