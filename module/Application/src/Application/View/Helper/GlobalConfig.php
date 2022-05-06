<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class GlobalConfig extends AbstractHelper
{


    private $globalTable;
    public function __construct($globalTable)
    {
        $this->globalTable = $globalTable;
    }

    public function __invoke()
    {
        return $this->globalTable->fetchAllGlobalConfigDataForMenu();
    }
}
