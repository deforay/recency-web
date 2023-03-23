<?php
namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;

class ResourcesTable extends AbstractTableGateway {

    protected $table = 'resources';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchAllResourceMap() {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $resourceQuery = $sql->select()->from('resources')
                                    ->order('display_name');
        $resourceQueryStr = $sql->getSqlStringForSqlObject($resourceQuery);
        $resourceResult = $dbAdapter->query($resourceQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $resourceResult;
    }
}
?>
