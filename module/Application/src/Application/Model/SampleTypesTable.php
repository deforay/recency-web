<?php

namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Laminas\Db\TableGateway\AbstractTableGateway;

class SampleTypesTable extends AbstractTableGateway
{

    protected $table = 'r_sample_types';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchAllSampleTypes()
    {
        return $this->select()->toArray();
    }
}
