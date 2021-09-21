<?php
namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

class UserFacilityMapTable extends AbstractTableGateway {

    protected $table = 'user_facility_map';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }
}
?>
