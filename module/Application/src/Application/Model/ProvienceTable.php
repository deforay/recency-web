<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class ProvienceTable extends AbstractTableGateway {

     protected $table = 'province_details';

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }
               public function fetchAllProvienceListApi()
          {
               $common = new CommonService();
               $config = new \Zend\Config\Reader\Ini();
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);

               $sQuery = $sql->select()->from(array('pd' => 'province_details'))->columns(array('province_id','province_name'));

               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               if($rResult) {
                    $response['status']='success';
                    $response['province'] = $rResult;
               }

               else {
                    $response["status"] = "fail";
                    $response["message"] = "Please select valid Provience details!";
               }
               return $response;
          }
     }
?>
