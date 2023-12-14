<?php
namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

class TestingFacilityTypeTable extends AbstractTableGateway {

    protected $table = 'testing_facility_type';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function fetchTestingFacilitiesTypeDetails()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
     
        $sQueryTest = $sql->select()->from(array('f' => 'testing_facility_type'))
            ->where(array('f.testing_facility_type_status' => 'active'));
        $sQueryStrTest = $sql->buildSqlString($sQueryTest);

        return $dbAdapter->query($sQueryStrTest, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    
    public function checkTestingFacilityTypeName($fName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('testing_facility_type')
            ->where(array('testing_facility_type_name' => trim($fName)));
       
        $fQueryStr = $sql->buildSqlString($fQuery); // Get the string of the Sql, instead of the Select-instance
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $fResult;
    }
}
?>
