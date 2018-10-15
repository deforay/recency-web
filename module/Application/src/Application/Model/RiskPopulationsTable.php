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

    public function checkFacilityName($rpName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $rpQuery = $sql->select()->from('risk_populations')->columns(array('rp_id','name'))
                        ->where(array('name' => trim($rpName)));
        $rpQueryStr = $sql->getSqlStringForSqlObject($rpQuery); // Get the string of the Sql, instead of the Select-instance
        $rpResult = $dbAdapter->query($rpQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rpResult;
    }
}
?>
