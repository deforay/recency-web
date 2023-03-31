<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class CustomConfig extends AbstractHelper
{
    private $configResult;
    public function __construct($configResult)
    {
        $this->configResult = $configResult;
    }

    public function __invoke()
    {
        return $this->configResult;
    }
}
