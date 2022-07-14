<?php
namespace Application\Model;

use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Session\Container;

class SettingsQcSampleTable extends AbstractTableGateway
{

    protected $table = 'qc_samples';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchSettingsSampleDetails($parameters)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $common = new CommonService();

        $aColumns = array('qc_sample_no', 'DATE_FORMAT(added_on,"%d-%b-%Y")','added_by','qc_sample_status');
        $orderColumns = array('qc_sample_no', 'added_on','added_by','qc_sample_status');

        /* Paging */
        $sLimit = "";
        if (isset($parameters['iDisplayStart']) && $parameters['iDisplayLength'] != '-1') {
            $sOffset = $parameters['iDisplayStart'];
            $sLimit = $parameters['iDisplayLength'];
        }

        /* Ordering */
        $sOrder = "";
        if (isset($parameters['iSortCol_0'])) {
            for ($i = 0; $i < intval($parameters['iSortingCols']); $i++) {
                if ($parameters['bSortable_' . intval($parameters['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        for ($i = 0; $i < count($aColumns); $i++) {
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

        $sQuery = $sql->select()->from(array('qcs' => 'qc_samples'))
        ->join(array('u'=>'users'),'qcs.added_by = u.user_id',array('user_name'))
        ;

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        //   echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $tResult = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($tResult);
        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($tResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        $role = $sessionLogin->roleCode;
        $update = true;
        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = ucwords($aRow['qc_sample_no']);
            $row[] = date('d-M-Y H:s A',strtotime($aRow['added_on']));
            $row[] = ucwords($aRow['user_name']);
            $row[] = ucwords($aRow['qc_sample_status']);
            $row[] = '<a href="/settings/edit-sample/' . base64_encode($aRow['qc_sample_id']) . '" class="btn btn-default" style="margin-right: 2px;" title="Edit"><i class="far fa-edit"></i>Edit</a>';
            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function addSampleSettingsDetails($params)
    {
        
        $logincontainer = new Container('credo');
        $common = new CommonService();
        if (isset($params['sampleNo']) && trim($params['sampleNo']) != "") {
            $data = array(
                'qc_sample_no' => $params['sampleNo'],
                'qc_sample_status' => $params['status'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId,
            );
            $this->insert($data);
            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchSettingsSampleDetailsById($sampleId)
    {

        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $common = new CommonService();
        $sQuery = $sql->select()->from(array('qcs' => 'qc_samples'))
            ->where(array('qcs.qc_sample_id' => $sampleId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
         return $rResult;
    }

    public function updateSampleSettingsDetails($params)
    {
        $logincontainer = new Container('credo');
        $mapDb = new \Application\Model\UserFacilityMapTable($this->adapter);
        $common = new CommonService();
        if (isset($params['sampleNo']) && trim($params['sampleNo']) != "") {
            $data = array(
                'qc_sample_no' => $params['sampleNo'],
                'qc_sample_status' => $params['status'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId,
            );
            $updateResult = $this->update($data, array('qc_sample_id' => $params['sampleId']));
        }
        return  $params['sampleId'];
    }

    public function fetchAllSampleListApi()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('qcs' => 'qc_samples'))
        ->columns(array('qcSampleId'=>'qc_sample_id','qcSampleNo'=>'qc_sample_no','qcSampleStatus'=>'qc_sample_status'))
        ->where(array('qc_sample_status'=>'active'))
        ;

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if($rResult) {
            $response['status']='success';
            $response['data'] = $rResult;
        }

        else {
            $response["status"] = "fail";
            $response["message"] = "No sample data's found!";
        }
        return $response;
    }
    
    public function fetchSamples(){
        return $this->select(array('qc_sample_status'=>'active'))->toArray();
    }
}
