<?php

namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;

class ManifestsTable extends AbstractTableGateway
{

    protected $table = 'manifests';
    protected $primary_id = 'manifest_id';
    protected $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchManifestById($manifestId)
    {
        return $this->select(array('manifest_id' => $manifestId))->current();
    }

    public function fetchManifests($parameters, $acl)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
        * you want to insert a non-database field (for example a counter or static image)
        */
        $sessionLogin = new Container('credo');

        $common = new CommonService();
        $aColumns = array('manifest_code', 'manifest_code', 'added_on', 'u.user_name');
        $orderColumns = array('manifest_code', 'manifest_code', 'added_on', 'u.user_name');

        /* Paging */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /* Ordering */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < (int) $parameters['iSortingCols']; $i++) {
                if ($parameters['bSortable_' . (int) $parameters['iSortCol_' . $i]] == "true") {
                    $sOrder .= $aColumns[(int) $parameters['iSortCol_' . $i]] . " " . ($parameters['sSortDir_' . $i]) . ",";
                }
            }
            $sOrder = substr_replace($sOrder, "", -1);
        }

        /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */

        $sWhere = "";
        if (isset($parameters['sSearch']) && $parameters['sSearch'] != "") {
            $searchArray = explode(" ", $parameters['sSearch']);
            $sWhereSub = "";
            foreach ($searchArray as $search) {
                if ($sWhereSub == "") {
                    $sWhereSub .= "(";
                } else {
                    $sWhereSub .= " AND (";
                }
                $colSize = count($aColumns);

                for ($i = 0; $i < $colSize; $i++) {
                    if ($i < $colSize - 1) {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
                    } else {
                        $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
                    }
                }
                $sWhereSub .= ")";
            }
            $sWhere .= $sWhereSub;
        }
        /* Individual column filtering */
        $counter = count($aColumns);

        /* Individual column filtering */
        for ($i = 0; $i < $counter; $i++) {
            if (isset($parameters['bSearchable_' . $i]) && $parameters['bSearchable_' . $i] == "true" && $parameters['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere .= $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                } else {
                    $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($parameters['sSearch_' . $i]) . "%' ";
                }
            }
        }

        /*
          * SQL queries
          * Get data to display
          */
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $roleId = $sessionLogin->roleId;
        $roleCode = $sessionLogin->roleCode;
        $userId = $sessionLogin->userId;

        $sQuery = $sql->select()->from(array('m' => 'manifests'))
            ->join(array('u' => 'users'), 'u.user_id = m.added_by', array('user_name'))
            ->join(array('r' => 'recency'), 'r.manifest_id = m.manifest_id', array('totalSamples' => new Expression('count(recency_id)')), 'left')
            ->group(array('m.manifest_id'));
        if ($roleCode != 'admin') {
            $sQuery->where(array('m.added_by' => $userId));
        }
        if (!empty($sWhere)) {
            $sQuery->where($sWhere);
        }
        if ($sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('r.facility_id IN (' . $sessionLogin->facilityMap . ')');
        }

        if (!empty($sOrder)) {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($tResult);
        $output = array(
            "sEcho" => (int) $parameters['sEcho'],
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($rResult as $aRow) {

            $update = false;
            $actionBtn = '';
            if ($acl->isAllowed($roleCode, 'Application\Controller\ManifestsController', 'edit')) {
                $actionBtn .= '<a href="/manifests/edit/' . base64_encode($aRow['manifest_id']) . '" class="btn btn-block btn-sm btn-danger" style="" title="Edit"><i class="far fa-edit"></i>Edit</a>';
                $update = true;
            }
            if ($acl->isAllowed($roleCode, 'Application\Controller\ManifestsController', 'genarate-manifest')) {
                $actionBtn .= '<a href="/manifests/genarate-manifest/' . base64_encode($aRow['manifest_id']) . '" class="btn btn-block btn-info btn-sm" style="" title="Genarate Manifest" target="_blank"><i class="fa fa-barcode"></i> Print Manifest</a>';
                $update = true;
            }
            $actionBtn .= '</div>';

            $row = array();
            $row[] = ucwords($aRow['manifest_code']);
            $row[] = $aRow['totalSamples'];
            $row[] = ucwords($aRow['added_on']);
            $row[] = ucwords($aRow['user_name']);
            if ($update) {
                $row[] = $actionBtn;
            }
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function addManifest($params)
    {

        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
        $manifestCode = $params['manifestCode'];
        $manifestId = null;

        $data = array(
            'manifest_code' => $manifestCode,
            'testing_site' => $params['testingSite'],
            'added_by' => $logincontainer->userId
        );

        $this->insert($data);
        $manifestId = $this->lastInsertValue;


        // loop through the selected samples and update Recency main table
        if ($manifestId !== 0 && $params['selectedRecencyId'] != '') {
            $recencyDb = new RecencyTable($this->adapter);
            $recencyList = explode(",", $params['selectedRecencyId']);
            foreach ($recencyList as $recencyId) {

                $updateData = array(
                    'manifest_id' => $manifestId,
                    'manifest_code' => $manifestCode,
                );

                $recencyDb->update($updateData, array('recency_id' => $recencyId));
            }
        }

        return $manifestCode;
    }

    public function updateManifest($params)
    {

        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $recencyDb = new RecencyTable($this->adapter);
        $manifestCode = $params['manifestCode'];
        $manifestId = $params['manifestId'];


        // making the id and code blank in recency table

        $updateData = array(
            'manifest_id' => null,
            'manifest_code' => null,
        );

        $recencyDb->update($updateData, array('manifest_id' => $manifestId));

        // loop through the selected samples and update Recency main table

        if ($params['selectedRecencyId'] != '') {

            $recencyList = explode(",", $params['selectedRecencyId']);

            foreach ($recencyList as $recencyId) {

                $updateData = array(
                    'manifest_id' => $manifestId,
                    'manifest_code' => $manifestCode,
                );

                $recencyDb->update($updateData, array('recency_id' => $recencyId));
            }
        }


        return $manifestCode;
    }

    public function fetchManifestsPDF($id)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('m' => $this->table))->columns(array('manifest_id', 'manifest_code'))
            ->join(array('r' => 'recency'), 'm.manifest_code=r.manifest_code', array('recency_id', 'sample_id', 'patient_id', 'dob', 'age', 'sample_collection_date', 'gender', 'patient_on_art', 'received_specimen_type'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('dd' => 'district_details'), 'f.district=dd.district_id', array('district_name'), 'left')
            ->join(array('st' => 'r_sample_types'), 'st.sample_id = r.received_specimen_type', array('sample_name'))
            ->where(array('m.' . $this->primary_id => $id));
        $sQueryStr = $sql->buildSqlString($sQuery);
        // echo $sQueryStr;die;
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}
