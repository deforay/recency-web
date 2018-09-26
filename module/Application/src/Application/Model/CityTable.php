<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class CityTable extends AbstractTableGateway {

     protected $table = 'city_details';

     public function __construct(Adapter $adapter) {
          $this->adapter = $adapter;
     }

          public function fetchAllCityListApi($params)
          {
               $common = new CommonService();
               $config = new \Zend\Config\Reader\Ini();
               $dbAdapter = $this->adapter;
               $sql = new Sql($dbAdapter);


               if(isset($params['districtId']) && $params['districtId']!=''){
                    $sQuery = $sql->select()->from(array('cd' => 'city_details'))->columns(array('city_id','district_id','city_name'))
                    ->where(array('district_id' => $params['districtId'] ));
               }
               else
               {
                    $sQuery = $sql->select()->from(array('cd' => 'city_details'))->columns(array('city_id','district_id','city_name'));
               }


               $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
               $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

               if($rResult) {
                    $response['status']='success';
                    $response['city'] = $rResult;
               }

               else {
                    $response["status"] = "fail";
                    $response["message"] = "Please select valid City detail!";
               }
               return $response;
          }
     }

?>
