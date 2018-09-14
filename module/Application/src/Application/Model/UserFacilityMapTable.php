<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

class UserFacilityMapTable extends AbstractTableGateway {

    protected $table = 'user_facility_map';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }
}
?>
