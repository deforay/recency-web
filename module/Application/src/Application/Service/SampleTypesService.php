<?php
namespace Application\Service;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;

class SampleTypesService {

    public $sm = null;

    public function __construct($sm = null) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getSampleTypesDetails()
    {
        $samplesTypesDb = $this->sm->get('SampleTypesTable');
        return $samplesTypesDb->fetchAllSampleTypes();
    }
}
