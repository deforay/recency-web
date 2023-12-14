<?php

namespace Application\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Laminas\Session\Container;
use Application\Service\CommonService;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author Jeyabanu
 */
class AuditRecencyTable extends AbstractTableGateway
{

    protected $table = 'audit_recency';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAuditRecencyDetails($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $recencyDb = new RecencyTable($this->adapter);

        $columnsSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE  table_name = 'audit_recency' order by ordinal_position";
        $response['auditColumns'] = $dbAdapter->query($columnsSql, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $columnsSql1 = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE  table_name = 'recency' order by ordinal_position";
        $response['recencyColumns'] = $dbAdapter->query($columnsSql1, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $sampleCode = $parameters['sampleCode'];
        $response['currentRecord'] = $recencyDb->fetchRecencyDetailsBySampleId($sampleCode);
        if (isset($sampleCode) && $sampleCode != '') {
            $sQuery = $sql->select()->from(array('a' => 'audit_recency'))
                ->where("(sample_id = '$sampleCode' OR patient_id = '$sampleCode')");
            $sQueryStr = $sql->buildSqlString($sQuery);
            $response['auditInfo'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please select valid Sample Code!";
        }
        return $response;
    }
}
