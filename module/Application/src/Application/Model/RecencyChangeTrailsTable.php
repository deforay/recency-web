<?php

namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;

class RecencyChangeTrailsTable extends AbstractTableGateway {
    protected $table = 'recency_change_trails';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}