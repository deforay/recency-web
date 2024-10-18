<?php
namespace Application\Service;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;

class RiskPopulationsService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getAllRiskPopulationsListApi($params)
    {
        $riskPopulationsDb = $this->sm->get('RiskPopulationsTable');
        return $riskPopulationsDb->fetchAllRiskPopulationsListApi($params);
    }
}
