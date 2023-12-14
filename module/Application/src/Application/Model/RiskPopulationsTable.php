<?php

namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

class RiskPopulationsTable extends AbstractTableGateway
{

    protected $table = 'risk_populations';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchAllRiskPopulationsListApi($params)
    {
        return $this->select()->toArray();
    }

    public function checkExistRiskPopulation($rpName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $rpQuery = $sql->select()->from('risk_populations')->columns(array('rp_id', 'name'))
            ->where(array('name' => trim($rpName)));
        $rpQueryStr = $sql->buildSqlString($rpQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($rpQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}
