<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;
use Zend\Config\Writer\PhpArray;

class SuperAdminTable extends AbstractTableGateway {

    protected $table = 'system_admin';

    public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
    }

    public function loginProcessDetails($params){
		$alertContainer = new Container('alert');
        $logincontainer = new Container('admin_credo');
        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        if(isset($params['loginEmail']) && trim($params['loginEmail'])!="" && trim($params['loginPassword'])!=""){
            $dbAdapter = $this->adapter;
            $sql = new Sql($dbAdapter);
            $password = sha1($params['loginPassword'].$configResult["password"]["salt"]);
            $sQuery = $sql->select()->from(array('s' => 'system_admin'))
				    ->where(array('email' => $params['loginEmail'], 'password' => $password));
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            //echo $sQueryStr;die;
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

            if($rResult) {
                        $logincontainer->adminId = $rResult->admin_id;
                        $logincontainer->adminName = ucwords($rResult->admin_name);
                        $logincontainer->adminEmail = ucwords($rResult->email);

                        return 'admin-home';
            }else {
                $alertContainer->alertMsg = 'The login id or password that you entered is incorrect';
                return 'admin-login';
            }
        }else {
            $alertContainer->alertMsg = 'The login id or password that you entered is incorrect';
            return 'admin-login';
        }
    }
    }
?>
