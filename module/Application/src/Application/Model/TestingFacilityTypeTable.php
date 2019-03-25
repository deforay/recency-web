<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

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
        $sQueryStrTest = $sql->getSqlStringForSqlObject($sQueryTest);
        $result = $dbAdapter->query($sQueryStrTest, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $result;
    }

    
    public function checkTestingFacilityTypeName($fName)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('testing_facility_type')
            ->where(array('testing_facility_type_name' => trim($fName)));
       
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery); // Get the string of the Sql, instead of the Select-instance
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $fResult;
    }
}
?>
