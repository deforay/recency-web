<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

class RiskPopulationsTable extends AbstractTableGateway {

    protected $table = 'risk_populations';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchAllRiskPopulationsListApi($params)
    {
        return $this->select()->toArray();
    }
}
?>
