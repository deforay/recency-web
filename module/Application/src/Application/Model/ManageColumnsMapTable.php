<?php
namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Config\Writer\PhpArray;
use Application\Service\CommonService;
use Zend\Db\TableGateway\AbstractTableGateway;

class ManageColumnsMapTable extends AbstractTableGateway {

    protected $table = 'manage_columns_map';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    
    public function mapManageColumnsDetails($params)
    {
        $logincontainer = new Container('credo');
        $str = implode (", ", $params['recencyManageColumns']);
        $common = new CommonService();
        $userId = $logincontainer->userId;
        $testftResult = $this->checkUserId($userId);
        if(isset($testftResult['user_id']) && $testftResult['user_id']!= ''){
            $data = array(
                //'user_id'=>$result,
                'manage_columns'=>$str,
            );
            $this->update($data,array('user_id'=> $testftResult['user_id']));
        }else{
            $dataNew = array(
                'user_id'=>$userId,
                'manage_columns'=>$str,
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
       
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery); // Get the string of the Sql, instead of the Select-instance
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $fResult;
    }

    public function fetchAllManagaColumnsDetails($userId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from('manage_columns_map')
            ->where(array('user_id' => $userId));
       
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery); // Get the string of the Sql, instead of the Select-instance
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        return $fResult;
    }
    
    }

?>
