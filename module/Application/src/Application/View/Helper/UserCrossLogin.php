<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class UserCrossLogin extends AbstractHelper
{


    private $userTable;
    public function __construct($userTable)
    {
        $this->userTable = $userTable;
    }

    public function __invoke()
    {
        return $this->userTable->fetchLoginUserDetials();
    }
}
