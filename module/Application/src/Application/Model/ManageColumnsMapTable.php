<?php

namespace Application\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Laminas\Db\TableGateway\AbstractTableGateway;

class ManageColumnsMapTable extends AbstractTableGateway
{

    protected $table = 'manage_columns_map';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }


    public function mapManageColumnsDetails($params)
    {
        $logincontainer = new Container('credo');
        $str = implode(", ", $params['recencyManageColumns']);
        $common = new CommonService();
        $userId = $logincontainer->userId;
        $testftResult = $this->checkUserId($userId);
        if (isset($testftResult['user_id']) && $testftResult['user_id'] != '') {
            $data = array(
                //'user_id'=>$result,
                'manage_columns' => $str,
            );
            $this->update($data, array('user_id' => $testftResult['user_id']));
        } else {
            $dataNew = array(
                'user_id' => $userId,
                'manage_columns' => $str,
            );
            //\Zend\Debug\Debug::dump($dataNew);die;
            $this->insert($dataNew);
        }
    }


    public function checkUserId($id)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('manage_columns_map')
            ->where(array('user_id' => $id));

        $fQueryStr = $sql->buildSqlString($fQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function fetchAllManagaColumnsDetails($userId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('manage_columns_map')
            ->where(array('user_id' => $userId));

        $fQueryStr = $sql->buildSqlString($fQuery); // Get the string of the Sql, instead of the Select-instance
        return $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }
}
