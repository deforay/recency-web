<?php

namespace Application\Service;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Session\Container;

class ManifestsService
{

    public $sm = null;

    public function __construct($sm = null)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }



    public function getManifests($params)
    {
        $manifestDb = $this->sm->get('ManifestsTable');
        $acl = $this->sm->get('AppAcl');
        return $manifestDb->fetchManifests($params,$acl);
    }

    public function addManifest($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $manifestDb = $this->sm->get('ManifestsTable');
            $result = $manifestDb->addManifest($params);
            if ($result !== false) {
                $adapter->commit();

                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Manifest added successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'manifest-add';
                $action                 = 'Added  Manifest ' . $result;
                $resourceName           = 'Manifests';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function fetchManifestById($manifestId)
    {
        $manifestDb = $this->sm->get('ManifestsTable');
        return $manifestDb->fetchManifestById($manifestId);
    }

    public function updateManifest($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $manifestDb = $this->sm->get('ManifestsTable');
            $result = $manifestDb->updateManifest($params);
            if ($result != false) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'Manifest updated successfully';
                // Add Event log
                $subject                = $result;
                $eventType              = 'manifest-edit';
                $action                 = 'Manifest updated for Manifest id ' . $result;
                $resourceName           = 'Manifests';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                // End Event log
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getManifestsPDF($id)
    {
        $manifestDb = $this->sm->get('ManifestsTable');
        // Add Event log
        $subject                = $id;
        $eventType              = 'manifest-print';
        $action                 = 'Manifest printed for Manifest id ' . $id;
        $resourceName           = 'Manifests';
        $eventLogDb             = $this->sm->get('EventLogTable');
        $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
        // End Event log        
        return $manifestDb->fetchManifestsPDF($id);
    }
}
