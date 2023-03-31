<?php

namespace Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendMail extends Command
{

    public $commonService = null;

    public function __construct(\Application\Service\CommonService $commonService)
    {
        $this->commonService = $commonService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->commonService->sendTempMail();
    }
}
