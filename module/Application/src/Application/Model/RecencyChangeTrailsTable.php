<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;

class RecencyChangeTrailsTable extends AbstractTableGateway
{
    protected $table = 'recency_change_trails';
    protected $adapter;
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }
}
