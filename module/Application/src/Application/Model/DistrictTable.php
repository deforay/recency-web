<?php
namespace Application\Model;

use Zend\Session\Container;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Application\Service\CommonService;

class DistrictTable extends AbstractTableGateway {

    protected $table = 'district_details';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
    public function fetchAllDistrictListApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        if(isset($params['provinceId']) && $params['provinceId']!=''){
                $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'))
                ->where(array('province_id' => $params['provinceId'] ));
        }
        else
        {
                $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'));
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if($rResult) {
                $response['status']='success';
                $response['district'] = $rResult;
        }

        else {
                $response["status"] = "fail";
                $response["message"] = "Please select valid District detail!";
        }
        return $response;
    }
    public function fetchDistrictDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        
        $sQuery = $sql->select()->from(array('dd' => 'district_details'))->columns(array('district_id','province_id','district_name'))
                            ->where(array('province_id' => $params['selectValue'] ));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        //fetch facility data
        $fQuery = $sql->select()->from(array('f' => 'facilities'))
                            ->where(array('province' => $params['selectValue']))
                            ->where('(facility_type_id IS NULL OR facility_type_id="" OR facility_type_id="1"  OR facility_type_id="0")');
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return array('district'=>$rResult,'facility'=>$fResult);

    }
}
?>