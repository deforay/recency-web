<?php

namespace Application\Model;

use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Session\Container;
use \Application\Model\CityTable;
use \Application\Model\DistrictTable;
use \Application\Model\FacilitiesTable;

use Laminas\Crypt\BlockCipher;
use Laminas\Crypt\Symmetric\Mcrypt;

class RecencyTable extends AbstractTableGateway
{

    protected $table = 'recency';
    public $vlResultOptionArray = array('target not detected', 'below detection line', 'tnd', 'bdl', 'failed', '<20', '<40', '< 20', '< 40', '< 400', '< 800', '<20', '<40');
    public $vlFailOptionArray = array('fail', 'failed');
    public $sessionLogin = null;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->sessionLogin = new Container('credo');
    }

    public function randomizer($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }
        return implode('', $pieces);
    }

    public function getSamplesWithoutManifestCode($testingFacilityId)
    {

        $sessionLogin = new Container('credo');
        // We only want samples which do not have Manifest generated (manifest_id is blank or null) AND are not yet tested (term_outcome is blank or null)
        $whereCondition = "(manifest_id='' OR manifest_id IS NULL) AND (term_outcome='' OR term_outcome IS NULL) AND (sample_id not like '' AND sample_id IS NOT NULL)";

        if ($sessionLogin->facilityMap != null) {
            $whereCondition .= ' AND facility_id IN (' . $sessionLogin->facilityMap . ') ';
        }
        if (!empty($testingFacilityId)) {
            $whereCondition .= ' AND testing_facility_id = ' . $testingFacilityId;
        }

        return $this->select($whereCondition)->toArray();
    }

    public function fetchSamplesByManifestId($manifestId)
    {

        $sessionLogin = new Container('credo');
        $whereCondition = "(manifest_id='$manifestId')";

        if ($sessionLogin->facilityMap != null) {
            $whereCondition .= ' AND  facility_id IN (' . $sessionLogin->facilityMap . ') ';
        }

        return $this->select($whereCondition)->toArray();
    }

    public function fetchRecencyDetails($parameters, $acl)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $sessionLogin = new Container('credo');
        $queryContainer = new Container('query');
        $role = $sessionLogin->roleId;
        $roleCode = $sessionLogin->roleCode;
        $common = new CommonService();

        $aColumns = array('r.sample_id', 'f.facility_name', 'DATE_FORMAT(r.hiv_recency_test_date,"%d-%b-%Y")', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'r.patient_id', 'DATE_FORMAT(r.sample_collection_date,"%d-%b-%Y")', 'DATE_FORMAT(r.sample_receipt_date,"%d-%b-%Y")', 'r.received_specimen_type', 'ft.facility_name', 'tf.testing_facility_type_name', 'DATE_FORMAT(r.hiv_diagnosis_date,"%d-%b-%Y")', 'r.kit_lot_no', 'DATE_FORMAT(r.kit_expiry_date,"%d-%b-%Y")', 'r.tester_name', 'DATE_FORMAT(r.dob,"%d-%b-%Y")', 'r.marital_status', 'r.residence', 'r.education_level', 'rp.name', 'r.pregnancy_status', 'r.current_sexual_partner', 'r.past_hiv_testing', 'r.last_hiv_status', 'r.patient_on_art', 'r.test_last_12_month', 'r.exp_violence_last_12_month', 'DATE_FORMAT(r.form_initiation_datetime ,"%d-%b-%Y %H:%i:%s")', 'DATE_FORMAT(r.form_transfer_datetime ,"%d-%b-%Y %H:%i:%s")');
        $orderColumns = array('r.sample_id', 'f.facility_name', 'r.hiv_recency_test_date', 'r.vl_test_date', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'r.patient_id', 'r.sample_collection_date', 'r.sample_receipt_date', 'r.received_specimen_type', 'f.facility_name', 'ft.facility_name', 'tf.testing_facility_type_name', 'r.hiv_diagnosis_date', 'r.kit_lot_no', 'r.kit_expiry_date', 'r.tester_name', 'r.dob', 'r.marital_status', 'r.residence', 'r.education_level', 'rp.name', 'r.pregnancy_status', 'r.current_sexual_partner', 'r.past_hiv_testing', 'r.last_hiv_status', 'r.patient_on_art', 'r.test_last_12_month', 'r.exp_violence_last_12_month', 'r.form_initiation_datetime', 'r.form_transfer_datetime');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        $sQuery = $sql->select()->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))->from(array('r' => 'recency'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('tf' => 'testing_facility_type'), 'tf.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'), 'left')
            ->join(array('st' => 'r_sample_types'), 'st.sample_id = r.received_specimen_type', array('sample_name'), 'left');
        //->order("r.recency_id DESC");
        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['province'] != '') {
            $sQuery->where(array('r.location_one' => $parameters['province']));
        }
        if ($parameters['district'] != '') {
            $sQuery->where(array('r.location_two' => $parameters['district']));
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['gender'] != '') {
            $sQuery->where(array('gender' => $parameters['gender']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['vlResult'] != '') {
            if ($parameters['vlResult'] == 'pending') {
                $sQuery->where(array('term_outcome' => 'Assay Recent'));
            } else if ($parameters['vlResult'] == 'vl_load_tested') {
                $sQuery->where('vl_result not like "" AND  vl_result is not NULL ');
            }
        }
        if ($parameters['RTest'] == 'pending') {
            $sQuery = $sQuery->where(array('term_outcome = "" OR  term_outcome IS NULL '));
        } else if ($parameters['RTest'] == 'completed') {
            $sQuery = $sQuery->where(array('term_outcome != "" AND  term_outcome IS NOT NULL '));
        } else {
            // all requests
        }

        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }

        if (isset($parameters['sampleCollectionDate']) && trim($parameters['sampleCollectionDate']) != '') {
            $s_c_date = explode("to", $_POST['sampleCollectionDate']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleCollectionDate'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($sessionLogin->facilityMap != null && $parameters['fName'] == '') {
            $sQuery = $sQuery->where('r.facility_id IN (' . $sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $sessionLogin->facilityMap . ')');
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }


        $queryContainer->exportRecencyDataQuery = $sQuery;


        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        $aResultFilterTotal = $dbAdapter->query("SELECT FOUND_ROWS() as `totalCount`", $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        foreach ($rResult as $aRow) {
            $pdfBtn = "";
            $actionBtn = "";
            $row = array();
            $formInitiationDate = '';
            if ($aRow['final_outcome'] != "") {
                $pdfBtn = '<a class="btn btn-success" href="javascript:void(0)" title="Generate Result PDF" onclick="generatePdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i></a>';
            }
            if ($aRow['form_initiation_datetime'] != '' && $aRow['form_initiation_datetime'] != '0000-00-00 00:00:00' && $aRow['form_initiation_datetime'] != null) {
                $formInitiationAry = explode(" ", $aRow['form_initiation_datetime']);
                $formInitiationDate = $common->humanDateFormat($formInitiationAry[0]) . " " . $formInitiationAry[1];
            }
            $formTransferDate = '';
            if ($aRow['form_transfer_datetime'] != '' && $aRow['form_transfer_datetime'] != '0000-00-00 00:00:00' && $aRow['form_transfer_datetime'] != null) {
                $formTransferAry = explode(" ", $aRow['form_transfer_datetime']);
                $formTransferDate = $common->humanDateFormat($formTransferAry[0]) . " " . $formTransferAry[1];
            }

            $update = false;
            $actionBtn = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">';
            if ($acl->isAllowed($roleCode, 'Application\Controller\RecencyController', 'edit')) {
                $actionBtn .= '<a class="btn btn-danger" title="Edit Sample"  href="/recency/edit/' . base64_encode($aRow['recency_id']) . '"><i class="si si-pencil"></i></a>';
                $update = true;
            }
            if ($acl->isAllowed($roleCode, 'Application\Controller\RecencyController', 'generate-pdf')) {
                $actionBtn .= $pdfBtn;
                $update = true;
            }
            $actionBtn .= '</div>';

            if ($update) {
                $row[] = $aRow['sample_id'] . '<br>' . $actionBtn;
            } else {
                $row[] = $aRow['sample_id'];
            }
            $row[] = $aRow['facility_name'];
            $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
            $row[] = $common->humanDateFormat($aRow['vl_test_date']);
            $row[] = ucwords($aRow['control_line']);
            $row[] = ucwords($aRow['positive_verification_line']);
            $row[] = ucwords($aRow['long_term_verification_line']);
            $row[] = $aRow['term_outcome'];
            $row[] = ucwords($aRow['vl_result']);
            $row[] = $aRow['final_outcome'];
            $row[] = ucwords($aRow['gender']);
            $row[] = $aRow['age'];

            $row[] = $aRow['patient_id'];
            $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
            $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
            $row[] = $aRow['sample_name'];

            $row[] = $aRow['testing_facility_name'];
            $row[] = $aRow['testing_facility_type_name'];

            $row[] = $common->humanDateFormat($aRow['hiv_diagnosis_date']);
            $row[] = $aRow['kit_lot_no'];
            $row[] = $common->humanDateFormat($aRow['kit_expiry_date']);

            $row[] = ucwords($aRow['tester_name']);
            $row[] = $common->humanDateFormat($aRow['dob']);

            $row[] = str_replace("_", " ", ucwords($aRow['marital_status']));
            $row[] = ucwords($aRow['residence']);
            $row[] = str_replace("_", " ", ucwords($aRow['education_level']));
            $row[] = ucwords($aRow['name']);
            $row[] = str_replace("_", " ", ucwords($aRow['pregnancy_status']));
            $row[] = str_replace("_", "-", $aRow['current_sexual_partner']);
            $row[] = ucwords($aRow['past_hiv_testing']);
            $row[] = ucwords($aRow['last_hiv_status']);
            $row[] = ucwords($aRow['patient_on_art']);
            $row[] = str_replace("_", " ", ucwords($aRow['test_last_12_month']));
            $row[] = str_replace("_", " ", ucwords($aRow['exp_violence_last_12_month']));

            $row[] = $formInitiationDate;
            $row[] = $formTransferDate;
            if ($update) {
                $row[] = $actionBtn;
            }

            $output['aaData'][] = $row;
        }

        return $output;
    }

    public function checkDistrictData($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $districtDb = new DistrictTable($this->adapter);

        $locationTwo = '';
        if (!isset($params['otherDistrictName'])) {
            $params['otherDistrictName'] = $params['otherDistrict'];
        }
        $dResult = $facilityDb->checkDistrictName(strtolower($params['otherDistrictName']), $params['location_one']);
        if (isset($dResult['district_name']) && $dResult['district_name'] != '') {
            $locationTwo = $dResult['district_id'];
        } else {
            $districtData = array(
                'province_id' => $params['location_one'],
                'district_name' => strtolower($params['otherDistrictName']),
            );
            $districtDb->insert($districtData);
            if ($districtDb->lastInsertValue > 0) {
                $locationTwo = $districtDb->lastInsertValue;
            }
        }

        if (isset($params['facilityId']) && $params['facilityId'] != '' && $locationTwo != '') {
            $facilityDb->update(array('district' => $locationTwo), array('facility_id' => base64_decode($params['facilityId'])));
        }
        return $locationTwo;
    }

    public function checkCityData($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $cityDb = new CityTable($this->adapter);

        $locationThree = '';
        if (!isset($params['otherCityName'])) {
            $params['otherCityName'] = $params['otherCity'];
        }
        $cResult = $facilityDb->checkCityName(strtolower($params['otherCityName']), $params['location_two']);
        if (isset($cResult['city_name']) && $cResult['city_name'] != '') {
            $locationThree = $cResult['city_id'];
        } else {
            $cityData = array(
                'district_id' => $params['location_two'],
                'city_name' => strtolower($params['otherCityName']),
            );
            $cityDb->insert($cityData);
            if ($cityDb->lastInsertValue > 0) {
                $locationThree = $cityDb->lastInsertValue;
            }
        }
        if (isset($params['facilityId']) && $params['facilityId'] != '' && $locationThree != '') {
            $facilityDb->update(array('city' => $locationThree), array('facility_id' => base64_decode($params['facilityId'])));
        }
        return $locationThree;
    }

    public function addRecencyDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $logincontainer = new Container('credo');
        $facilityDb = new FacilitiesTable($this->adapter);
        $districtDb = new DistrictTable($this->adapter);
        $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
        $cityDb = new CityTable($this->adapter);
        // $facilityTypeDb = new FacilitiesTypeTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $common = new CommonService();
        $lastInsertedId = null;
        if ((isset($params['sampleId']) && trim($params['sampleId']) != "") || (isset($params['patientId']) && trim($params['patientId']) != "")) {
            if ($params['facilityId'] == 'other') {
                $fResult = $facilityDb->checkFacilityName(strtolower($params['otherFacilityName']), 1);
                if (isset($fResult['facility_name']) && $fResult['facility_name'] != '') {
                    $params['facilityId'] = base64_encode($fResult['facility_id']);
                } else {
                    if ($params['location_two'] == 'other') {
                        $params['location_two'] = $this->checkDistrictData($params);
                    }
                    if ($params['location_three'] == 'other') {
                        $params['location_three'] = $this->checkCityData($params);
                    }
                    $facilityData = array(
                        'facility_name' => trim($params['otherFacilityName']),
                        'province' => $params['location_one'],
                        'district' => $params['location_two'],
                        'city' => $params['location_three'],
                        'facility_type_id' => '1',
                        'status' => 'active'
                    );
                    $facilityDb->insert($facilityData);
                    if ($facilityDb->lastInsertValue > 0) {
                        $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }

            if ($params['location_two'] == 'other') {
                $params['location_two'] = $this->checkDistrictData($params);
            }
            if ($params['location_three'] == 'other') {
                $params['location_three'] = $this->checkCityData($params);
            }

            if ($params['testingFacilityId'] == 'other') {

                $ftResult = $facilityDb->checkFacilityName(strtolower($params['otherTestingFacility']), 2);
                if (isset($ftResult['facility_name']) && $ftResult['facility_name'] != '') {
                    $params['testingFacilityId'] = base64_encode($ftResult['facility_id']);
                } else {
                    // echo "else2";die;
                    $facilityData = array(
                        'facility_name' => trim($params['otherTestingFacility']),
                        'province' => $params['location_one'],
                        'district' => $params['location_two'],
                        'city' => $params['location_three'],
                        'facility_type_id' => '2',
                        'status' => 'active'
                    );
                    $facilityDb->insert($facilityData);
                    if ($facilityDb->lastInsertValue > 0) {
                        $params['testingFacilityId'] = base64_encode($facilityDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }

            if ($params['testingModality'] == 'other') {

                $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                if (isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name'] != '') {
                    $params['testingModality'] = $testftResult['testing_facility_type_id'];
                } else {
                    // echo "else2";die;
                    $testFacilityTypeData = array(
                        'testing_facility_type_name' => $params['othertestingmodality'],
                        'testing_facility_type_status' => 'active'
                    );
                    $TestingFacilityTypeDb->insert($testFacilityTypeData);
                    if ($TestingFacilityTypeDb->lastInsertValue > 0) {
                        $params['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
                    } else {
                        return false;
                    }
                }
            }

            //  check oher pouplation
            if ($params['riskPopulation'] == 'Other') {
                $rpResult = $riskPopulationDb->checkExistRiskPopulation($params['otherRiskPopulation']);
                if (isset($rpResult['name']) && $rpResult['name'] != '') {
                    $params['riskPopulation'] = base64_encode($rpResult['rp_id']);
                } else {
                    $rpData = array('name' => trim($params['otherRiskPopulation']));
                    $riskPopulationDb->insert($rpData);
                    if ($riskPopulationDb->lastInsertValue > 0) {
                        $params['riskPopulation'] = base64_encode($riskPopulationDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }
            $recencySampleId = $this->fetchSampleId();
            $data = array(
                'sample_id' => $recencySampleId['recencyId'],
                'sample_prefix_id' => $recencySampleId['sample_prefix_id'],
                'sample_id_string_prefix' => $recencySampleId['sample_id_string_prefix'],
                'sample_id_year_prefix' => $recencySampleId['sample_id_year_prefix'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'testing_facility_id' => ($params['testingFacilityId'] != '') ? base64_decode($params['testingFacilityId']) : null,
                'dob' => ($params['dob'] != '') ? $common->dbDateFormat($params['dob']) : null,
                'hiv_diagnosis_date' => ($params['hivDiagnosisDate'] != '') ? $common->dbDateFormat($params['hivDiagnosisDate']) : null,
                'hiv_recency_test_date' => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate'] != '') ? $common->dbDateFormat($params['hivRecencyTestDate']) : null,
                'recency_test_performed' => $params['recencyTestPerformed'],
                'recency_test_not_performed' => ($params['recencyTestPerformed'] == 'true') ? $params['recencyTestNotPerformed'] : null,
                'other_recency_test_not_performed'  => ($params['recencyTestNotPerformed'] == 'other') ? $params['otherRecencyTestNotPerformed'] : null,
                'control_line'                    => (isset($params['controlLine']) && $params['controlLine'] != '') ? $params['controlLine'] : null,
                'positive_verification_line'      => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine'] != '') ? $params['positiveVerificationLine'] : null,
                'long_term_verification_line'     => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine'] != '') ? $params['longTermVerificationLine'] : null,
                'term_outcome'                    => (isset($params['outcomeData']) && $params['outcomeData'] != "") ? $params['outcomeData'] : $params['outcomeData'],
                'final_outcome'                   => $params['vlfinaloutcomeResult'],
                'age_not_reported'                => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : 'no',
                'gender'                          => $params['gender'],
                'age'                             => ($params['age'] != '') ? $params['age'] : null,
                'marital_status'                  => $params['maritalStatus'],
                'residence'                       => $params['residence'],
                'education_level'                 => $params['educationLevel'],
                'risk_population'                 => base64_decode($params['riskPopulation']),
                'pregnancy_status'                => $params['pregnancyStatus'],
                'current_sexual_partner'          => $params['currentSexualPartner'],
                'past_hiv_testing'                => $params['pastHivTesting'],
                'last_hiv_status'                 => $params['lastHivStatus'],
                'patient_on_art'                  => $params['patientOnArt'],
                'test_last_12_month'              => $params['testLast12Month'],
                'location_one'                    => $params['location_one'],
                'location_two'                    => $params['location_two'],
                'location_three'                  => $params['location_three'],
                'exp_violence_last_12_month'      => $params['expViolence'],
                'notes'                           => $params['comments'],
                'added_on'                        => date("Y-m-d H:i:s"),
                'added_by'                        => $logincontainer->userId,
                'form_initiation_datetime'        => date("Y-m-d H:i:s"),
                'form_transfer_datetime'          => date("Y-m-d H:i:s"),
                // 'kit_name'                        =>  $params['testKitName'],
                'kit_lot_no'                      => $params['testKitLotNo'],
                'kit_expiry_date'                 => ($params['testKitExpDate'] != '') ? $common->dbDateFormat($params['testKitExpDate']) : null,
                'vl_request_sent'                 => isset($params['sendVlsm']) ? $params['sendVlsm'] : 'no',
                'vl_request_sent_date_time'       => (isset($params['sendVlsm']) && $params['sendVlsm'] == 'yes') ? $common->getDateTime() : null,
                'tester_name'                     => $params['testerName'],
                'vl_test_date'                    => ($params['vlTestDate'] != '') ? $common->dbDateFormat($params['vlTestDate']) : null,
                'vl_lab'                          => ($params['isVlLab'] != '') ? $params['isViralLabText'] : null,
                // 'vl_result'                       => ($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                'sample_collection_date'          => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate'] != '') ? $common->dbDateFormat($params['sampleCollectionDate']) : null,
                'sample_receipt_date'             => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate'] != '') ? $common->dbDateFormat($params['sampleReceiptDate']) : null,
                'received_specimen_type'          => $params['receivedSpecimenType'],
                'unique_id'                       => $this->randomizer(10),
                'testing_facility_type'           => $params['testingModality']
            );
            if ($params['positiveVerificationLineActual'] != '') {
                $data['invalid_control_line']       = $params['controlLineActual'];
                $data['invalid_verification_line']  = $params['positiveVerificationLineActual'];
                $data['invalid_longterm_line']      = $params['longTermVerificationLineActual'];
            }
            if ($params['vlLoadResult'] != '') {
                $data['vl_result'] = $params['vlLoadResult'];
                $data['vl_result_entry_date'] = date("Y-m-d H:i:s");
            } else if ($params['vlResultOption']) {
                $data['vl_result'] = htmlentities($params['vlResultOption']);
                $data['vl_result_entry_date'] = date("Y-m-d H:i:s");
            }

            if ((isset($params['outcomeDataActual']) && $params['outcomeDataActual'] != "") || (isset($params['outcomeData']) && $params['outcomeData'] != "")) {
                $data['assay_outcome_updated_by']       = $logincontainer->userId;
                $data['assay_outcome_updated_on']       = $common->getDateTime();
            }
            if (isset($params['vlfinaloutcomeResult']) && $params['vlfinaloutcomeResult'] != "") {
                $data['final_outcome_updated_by']       = $logincontainer->userId;
                $data['final_outcome_updated_on']       = $common->getDateTime();
            }
            if ($logincontainer->roleCode == 'remote_order_user') {
                $data['remote_order']       = 'yes';
            }

            //  if (strpos($params['outcomeData'], 'Long Term') !== false){
            //       $data['final_outcome'] = 'Long Term';
            //  }else if (strpos($params['outcomeData'], 'Invalid') !== false){
            //       $data['final_outcome'] = 'Invalid';
            //  }else if (strpos($params['outcomeData'], 'Negative') !== false){
            //       $data['final_outcome'] = 'Assay Negative';
            //  }

            $this->insert($data);

            $lastInsertedId = $this->lastInsertValue;
        }
        return $lastInsertedId;
    }

    public function fetchRecencyDetailsById($recencyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('recency')
            ->where(array('recency_id' => $recencyId));
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function fetchRecencyDetailsBySampleId($sampleId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('recency')
            ->where("(sample_id = '$sampleId' OR patient_id = '$sampleId')");
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function fetchRecencyDetailsForPDF($recencyId, $sm)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName' => 'facility_name'))
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->join(array('st' => 'r_sample_types'), 'st.sample_id = r.received_specimen_type', array('sample_name'), 'left')
            ->where(array('recency_id' => $recencyId));

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();

        // Add event log
        $filename = 'Recency-Result-' . date('d-M-Y-H-i-s') . '.pdf';
        $subject                = $filename;
        $eventType              = 'Recency data-pdf';
        $action                 = 'Downloaded Recency data ';
        $resourceName           = 'Recency data ';
        $eventLogDb             = $sm->get('EventLogTable');
        $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
        return $rResult;
    }

    public function updateRecencyDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $cloneDb = new RecencyChangeTrailsTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $districtDb = new DistrictTable($this->adapter);
        $cityDb = new CityTable($this->adapter);
        $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
        $logincontainer = new Container('credo');
        $common = new CommonService();

        if (isset($params['recencyId']) && trim($params['recencyId']) != "") {
            if ($params['facilityId'] == 'other') {
                $fResult = $facilityDb->checkFacilityName(strtolower($params['otherFacilityName']), 1);
                if (isset($fResult['facility_name']) && $fResult['facility_name'] != '') {
                    $params['facilityId'] = base64_encode($fResult['facility_id']);
                } else {

                    if ($params['location_two'] == 'other') {
                        $params['location_two'] = $this->checkDistrictData($params);
                    }
                    if ($params['location_three'] == 'other') {
                        $params['location_three'] = $this->checkCityData($params);
                    }

                    $facilityData = array(
                        'facility_name' => trim($params['otherFacilityName']),
                        'province' => $params['location_one'],
                        'district' => $params['location_two'],
                        'city' => $params['location_three'],
                        'facility_type_id' => '1',
                        'status' => 'active'
                    );
                    $facilityDb->insert($facilityData);
                    if ($facilityDb->lastInsertValue > 0) {
                        $params['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }

            if ($params['location_two'] == 'other') {
                $params['location_two'] = $this->checkDistrictData($params);
            }
            if ($params['location_three'] == 'other') {
                $params['location_three'] = $this->checkCityData($params);
            }

            if ($params['testingFacilityId'] == 'other') {
                $ftResult = $facilityDb->checkFacilityName(strtolower($params['otherTestingFacility']), 2);
                if (isset($ftResult['facility_name']) && $ftResult['facility_name'] != '') {
                    $params['testingFacilityId'] = base64_encode($ftResult['facility_id']);
                } else {
                    $facilityData = array(
                        'facility_name' => trim($params['otherTestingFacility']),
                        'province' => $params['location_one'],
                        'district' => $params['location_two'],
                        'city' => $params['location_three'],
                        'facility_type_id' => '2',
                        'status' => 'active'
                    );
                    $facilityDb->insert($facilityData);
                    if ($facilityDb->lastInsertValue > 0) {
                        $params['testingFacilityId'] = base64_encode($facilityDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }

            if ($params['testingModality'] == 'other') {

                $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                if (isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name'] != '') {
                    $params['testingModality'] = $testftResult['testing_facility_type_id'];
                } else {
                    // echo "else2";die;
                    $testFacilityTypeData = array(
                        'testing_facility_type_name' => $params['othertestingmodality'],
                        'testing_facility_type_status' => 'active'
                    );
                    $TestingFacilityTypeDb->insert($testFacilityTypeData);
                    if ($TestingFacilityTypeDb->lastInsertValue > 0) {
                        $params['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
                    } else {
                        return false;
                    }
                }
            }

            //check oher pouplation
            if ($params['riskPopulation'] == 'Other') {
                $rpResult = $riskPopulationDb->checkExistRiskPopulation($params['otherRiskPopulation']);
                if (isset($rpResult['name']) && $rpResult['name'] != '') {
                    $params['riskPopulation'] = base64_encode($rpResult['rp_id']);
                } else {
                    $rpData = array('name' => trim($params['otherRiskPopulation']));
                    $riskPopulationDb->insert($rpData);
                    if ($riskPopulationDb->lastInsertValue > 0) {
                        $params['riskPopulation'] = base64_encode($riskPopulationDb->lastInsertValue);
                    } else {
                        return false;
                    }
                }
            }

            $data = array(
                'patient_id'                      => $params['patientId'],
                'facility_id'                     => base64_decode($params['facilityId']),
                'testing_facility_id'             => ($params['testingFacilityId'] != '') ? base64_decode($params['testingFacilityId']) : null,
                'dob'                             => ($params['dob'] != '') ? $common->dbDateFormat($params['dob']) : null,
                'hiv_diagnosis_date'              => ($params['hivDiagnosisDate'] != '') ? $common->dbDateFormat($params['hivDiagnosisDate']) : null,
                'hiv_recency_test_date'           => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate'] != '') ? $common->dbDateFormat($params['hivRecencyTestDate']) : null,
                'recency_test_performed'          => $params['recencyTestPerformed'],
                'recency_test_not_performed'      => ($params['recencyTestPerformed'] == 'true') ? $params['recencyTestNotPerformed'] : null,
                'other_recency_test_not_performed'  => (isset($params['recencyTestPerformed']) && $params['recencyTestPerformed'] = 'other') ? $params['otherRecencyTestNotPerformed'] : null,
                'control_line'                    => (isset($params['controlLine']) && $params['controlLine'] != '') ? $params['controlLine'] : null,
                'positive_verification_line'      => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine'] != '') ? $params['positiveVerificationLine'] : null,
                'long_term_verification_line'     => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine'] != '') ? $params['longTermVerificationLine'] : null,
                'term_outcome'                    => (isset($params['outcomeData']) && $params['outcomeData'] != "") ? $params['outcomeData'] : $params['outcomeData'],
                'final_outcome'                   => $params['vlfinaloutcomeResult'],
                'gender'                          => $params['gender'],
                'age_not_reported'                => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : 'no',
                'age'                             => ($params['age'] != '') ? $params['age'] : null,
                'marital_status'                  => $params['maritalStatus'],
                'residence'                       => $params['residence'],
                'education_level'                 => $params['educationLevel'],
                'risk_population'                 => base64_decode($params['riskPopulation']),
                'pregnancy_status'                => $params['pregnancyStatus'],
                'current_sexual_partner'          => $params['currentSexualPartner'],
                'past_hiv_testing'                => $params['pastHivTesting'],
                'last_hiv_status'                 => $params['lastHivStatus'],
                'patient_on_art'                  => $params['patientOnArt'],
                'test_last_12_month'              => $params['testLast12Month'],
                'location_one'                    => $params['location_one'],
                'location_two'                    => $params['location_two'],
                'location_three'                  => $params['location_three'],
                'exp_violence_last_12_month'      => $params['expViolence'],
                'notes'                           => $params['comments'],
                'kit_lot_no'                      => $params['testKitLotNo'],
                // 'kit_name'                        =>$params['testKitName'],
                'kit_expiry_date'                 => ($params['testKitExpDate'] != '') ? $common->dbDateFormat($params['testKitExpDate']) : null,
                'tester_name'                     => $params['testerName'],
                'form_saved_datetime'             => date('Y-m-d H:i:s'),
                'vl_test_date'                    => ($params['vlTestDate'] != '') ? $common->dbDateFormat($params['vlTestDate']) : null,
                // 'vl_result'                       =>($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                'sample_collection_date'          => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate'] != '') ? $common->dbDateFormat($params['sampleCollectionDate']) : null,
                'sample_receipt_date'             => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate'] != '') ? $common->dbDateFormat($params['sampleReceiptDate']) : null,
                'received_specimen_type'          => $params['receivedSpecimenType'],
                'testing_facility_type'           => $params['testingModality']
            );

            if ($params['positiveVerificationLineActual'] != '') {
                $data['invalid_control_line']       = $params['controlLineActual'];
                $data['invalid_verification_line']  = $params['positiveVerificationLineActual'];
                $data['invalid_longterm_line']      = $params['longTermVerificationLineActual'];
            }

            if ($params['vlLoadResult'] != '') {
                $data['vl_result'] = $params['vlLoadResult'];
            } else if ($params['vlResultOption']) {
                $data['vl_result'] = htmlentities($params['vlResultOption']);
            }
            if ((isset($params['outcomeDataActual']) && $params['outcomeDataActual'] != "") || (isset($params['outcomeData']) && $params['outcomeData'] != "")) {
                $data['assay_outcome_updated_by']       = $logincontainer->userId;
                $data['assay_outcome_updated_on']       = $common->getDateTime();
            }
            if (isset($params['vlfinaloutcomeResult']) && $params['vlfinaloutcomeResult'] != "") {
                $data['final_outcome_updated_by']       = $logincontainer->userId;
                $data['final_outcome_updated_on']       = $common->getDateTime();
            }
            if ($logincontainer->roleCode == 'remote_order_user') {
                $data['remote_order']       = 'yes';
            }
            //  if (strpos($params['outcomeData'], 'Long Term') !== false)
            //            {
            //             $data['final_outcome'] = 'Long Term';
            //            }
            //            else if (strpos($params['outcomeData'], 'Invalid') !== false)
            //            {
            //             $data['final_outcome'] = 'Invalid';
            //            }else if (strpos($params['outcomeData'], 'Negative') !== false)
            //            {
            //             $data['final_outcome'] = 'Assay Negative';
            //            }
            $updateResult = $this->update($data, array('recency_id' => $params['recencyId']));
        }
        $recencyId = 0;
        if ($updateResult > 0) {
            $recencyId = $params['recencyId'];
            $modifyData = array(
                'modified_on' => $common->getDateTime(),
                'modified_by' => $logincontainer->userId
            );
            $this->update($modifyData, array('recency_id' => $params['recencyId']));
            $explode = explode(',', $params['oldRecords']);
            $explodeInd = explode(',', $params['oldRecordsInd']);
            $cloneData = array();
            foreach ($explodeInd as $key => $val) {
                $cloneData[$val] = $explode[$key];
            }
            $cloneData['trail_created_on'] = $common->getDateTime();
            $cloneData['trail_created_by'] = $logincontainer->userId;
            $cloneDb->insert($cloneData);
        }
        return $recencyId;
    }

    public function fetchAllRecencyListApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status', 'secret_key'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->buildSqlString($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $secretKey = $uResult['secret_key'];
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $rececnyQuery = $sql->select()->from(array('r' => 'recency'))
                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name', 'province'))
                ->join(array('u' => 'users'), 'u.user_id = r.added_by', array());
            if ($uResult['role_code'] != 'admin') {
                $rececnyQuery = $rececnyQuery->where(array('u.auth_token' => $params['authToken'], 'r.added_by' => $uResult['user_id']));
            }

            if (isset($params['start']) && isset($params['end'])) {
                $rececnyQuery = $rececnyQuery->where(
                    array(
                        "((r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) . "'",
                        "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end'])) . "') OR
                                                (r.hiv_recency_test_date is null or r.hiv_recency_test_date = '' or r.hiv_recency_test_date ='0000-00-00 00:00:00'))",
                    )
                );
            }
            $recencyQueryStr = $sql->buildSqlString($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                $response['status'] = 'success';
                if ($params['version'] > 2.8 && $secretKey != "" && $params["version"] != null) {
                    $response['recency'] = $this->cryptoJsAesEncrypt($secretKey, $recencyResult);
                } else
                    $response['recency'] = $recencyResult;
            } else {
                $response["status"] = "fail";
                $response["message"] = "You don't have recency data!";
            }
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your token credentials!";
        }
        return $response;
    }

    public function fetchAllRecencyResultWithVlListApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status', 'secret_key'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->buildSqlString($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $secretKey = $uResult['secret_key'];
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $rececnyQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_test_date', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                ->join(array('u' => 'users'), 'u.user_id = r.added_by', array())
                ->where(array(new \Laminas\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));
            if ($uResult['role_code'] != 'admin') {
                $rececnyQuery = $rececnyQuery->where(array('u.auth_token' => $params['authToken']));
            }
            if (isset($params['start']) && isset($params['end'])) {
                $rececnyQuery = $rececnyQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) . "'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end'])) . "'"));
            }
            $recencyQueryStr = $sql->buildSqlString($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                $response['status'] = 'success';
                if ($params['version'] > 2.8 && $secretKey != "" && $params["version"] != null) {
                    $response['recency'] = $this->cryptoJsAesEncrypt($secretKey, $recencyResult);
                } else
                    $response['recency'] = $recencyResult;
            } else {
                $response["status"] = "fail";
                $response["message"] = "You don't have recency data!";
            }
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your token credentials!";
        }
        return $response;
    }

    // Pending
    public function fetchAllPendingVlResultListApi($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status', 'secret_key'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->buildSqlString($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $secretKey = $uResult['secret_key'];
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $rececnyQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_test_date', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result'))
                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                ->join(array('u' => 'users'), 'u.user_id = r.added_by', array())
                ->where("((r.vl_result IS NULL OR r.vl_result = '') AND  r.term_outcome='Assay Recent')");
            if ($uResult['role_code'] != 'admin') {
                $rececnyQuery = $rececnyQuery->where(array('u.auth_token' => $params['authToken']));
            }
            $recencyQueryStr = $sql->buildSqlString($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                $response['status'] = 'success';
                if ($params['version'] > 2.8 && $secretKey != "" && $params["version"] != null) {
                    $response['recency'] = $this->cryptoJsAesEncrypt($secretKey, $recencyResult);
                } else
                    $response['recency'] = $recencyResult;
            } else {
                $response["status"] = "fail";
                $response["message"] = "You don't have recency data!";
            }
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your token credentials!";
        }
        return $response;
    }

    public function addRecencyDetailsApi($params)
    {

        $dbAdapter = $this->adapter;
        $adapter = $dbAdapter->getDriver()->getConnection();
        $sql = new Sql($dbAdapter);
        $facilityDb = new FacilitiesTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $globalDb = new GlobalConfigTable($this->adapter);
        $districtDb = new DistrictTable($this->adapter);
        $cityDb = new CityTable($this->adapter);
        $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
        $common = new CommonService();
        $userId = $params["userId"];
        if (isset($params["form"])) {

            $uQuery = $sql->select()->from('users')
                ->where(array('user_id' => $userId));
            $uQueryStr = $sql->buildSqlString($uQuery); // Get the string of the Sql, instead of the Select-instance
            $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
                $adminEmail = $globalDb->getGlobalValue('admin_email');
                $adminPhone = $globalDb->getGlobalValue('admin_phone');
                $response['message'] = 'Your password has expired or has been locked, please contact your administrator(' . $adminEmail . ' or ' . $adminPhone . ')';
                $response['status'] = 'failed';
                return $response;
            }
            $i = 0;
            if ($userId != '' && $params["version"] > "2.8" && $params["version"] != null) {
                $secretKey = $uResult['secret_key'];
                $arrayCount = count($params['form']);
                $formsVal = array();
                for ($x = 0; $x < $arrayCount; $x++) {
                    if ($secretKey != "")
                        $formsVal[] = $this->cryptoJsAesDecrypt($secretKey, $params['form'][$x]);
                    else
                        $formsVal[] = $params['form'][$x];
                }
                $formData = $formsVal;
            } else {
                $arrayCount = count($params['form']);
                $formsVal = array();
                for ($x = 0; $x < $arrayCount; $x++) {
                    $formsVal[] = $params['form'][$x];
                }
                $formData = $formsVal;
            }
            foreach ($formData as $key => $recency) {
                try {
                    if (isset($recency['patientId']) && trim($recency['patientId']) != "") {
                        if ($recency['otherfacility'] != '') {
                            $fResult = $facilityDb->checkFacilityName(strtolower($recency['otherfacility']), 1);
                            if (isset($fResult['facility_name']) && $fResult['facility_name'] != '') {
                                $recency['facilityId'] = base64_encode($fResult['facility_id']);
                            } else {

                                if ($recency['otherDistrict'] != '') {
                                    $recency['location_two'] = $this->checkDistrictData($recency);
                                }
                                if ($recency['otherCity'] != '') {
                                    $recency['location_three'] = $this->checkCityData($recency);
                                }

                                $facilityData = array(
                                    'facility_name' => trim($recency['otherfacility']),
                                    'province' => $recency['location_one'],
                                    'district' => $recency['location_two'],
                                    'city' => $recency['location_three'],
                                    'facility_type_id' => '1',
                                    'status' => 'active',
                                );
                                $facilityDb->insert($facilityData);
                                if ($facilityDb->lastInsertValue > 0) {
                                    $recency['facilityId'] = base64_encode($facilityDb->lastInsertValue);
                                }
                            }
                        } else {
                            $recency['facilityId'] = (isset($recency['facilityId']) && !empty($recency['facilityId'])) ? base64_encode($recency['facilityId']) : null;
                        }

                        if (isset($recency['otherDistrict']) && $recency['otherDistrict'] != '') {
                            $recency['location_two'] = $this->checkDistrictData($recency);
                        }
                        if (isset($recency['otherCity']) && $recency['otherCity'] != '') {
                            $recency['location_three'] = $this->checkCityData($recency);
                        }

                        if ($recency['othertestingfacility'] != '') {
                            $fResult = $facilityDb->checkFacilityName(strtolower($recency['othertestingfacility']), 2);
                            if (isset($fResult['facility_name']) && $fResult['facility_name'] != '') {
                                $recency['testingFacility'] = $fResult['facility_id'];
                            } else {
                                $facilityData = array(
                                    'facility_name' => trim($recency['othertestingfacility']),
                                    'province' => $recency['location_one'],
                                    'district' => $recency['location_two'],
                                    'city' => $recency['location_three'],
                                    'facility_type_id' => '2',
                                    'status' => 'active',
                                );
                                $facilityDb->insert($facilityData);
                                if ($facilityDb->lastInsertValue > 0) {
                                    $recency['testingFacility'] = $facilityDb->lastInsertValue;
                                }
                            }
                        } else {
                            $recency['testingFacility'] = (isset($recency['testingFacility']) && !empty($recency['testingFacility'])) ? ($recency['testingFacility']) : null;
                        }

                        if ($recency['othertestingmodality'] != '') {

                            $testftResult = $TestingFacilityTypeDb->checkTestingFacilityTypeName(strtolower($params['othertestingmodality']));
                            if (isset($testftResult['testing_facility_type_name']) && $testftResult['testing_facility_type_name'] != '') {
                                $recency['testingModality'] = $testftResult['testing_facility_type_id'];
                            } else {
                                // echo "else2";die;
                                $testFacilityTypeData = array(
                                    'testing_facility_type_name' => $recency['othertestingmodality'],
                                    'testing_facility_type_status' => 'active'
                                );
                                $TestingFacilityTypeDb->insert($testFacilityTypeData);
                                if ($TestingFacilityTypeDb->lastInsertValue > 0) {
                                    $recency['testingModality'] = $TestingFacilityTypeDb->lastInsertValue;
                                } else {
                                    return false;
                                }
                            }
                        } else {
                            $recency['testingModality'] = (isset($recency['testingModality']) && !empty($recency['testingModality'])) ? ($recency['testingModality']) : null;
                        }

                        //check oher pouplation
                        if ($recency['otherriskPopulation'] != '') {
                            $rpResult = $riskPopulationDb->checkExistRiskPopulation($recency['otherriskPopulation']);
                            if (isset($rpResult['name']) && $rpResult['name'] != '') {
                                $recency['riskPopulation'] = $rpResult['rp_id'];
                            } else {
                                $rpData = array('name' => trim($recency['otherriskPopulation']));
                                $riskPopulationDb->insert($rpData);
                                if ($riskPopulationDb->lastInsertValue > 0) {
                                    $recency['riskPopulation'] = $riskPopulationDb->lastInsertValue;
                                }
                            }
                        }
                        $adapter->beginTransaction();
                        $recencySampleId = $this->fetchSampleId();
                        $data = array(
                            'sample_id' => $recencySampleId['recencyId'],
                            'sample_prefix_id' => $recencySampleId['sample_prefix_id'],
                            'sample_id_string_prefix' => $recencySampleId['sample_id_string_prefix'],
                            'sample_id_year_prefix' => $recencySampleId['sample_id_year_prefix'],
                            'patient_id' => $recency['patientId'],
                            'sample_collection_date' => (isset($recency['sampleCollectionDate']) && $recency['sampleCollectionDate'] != '') ? $common->dbDateFormat($recency['sampleCollectionDate']) : null,
                            'sample_receipt_date' => (isset($recency['sampleReceiptDate']) && $recency['sampleReceiptDate'] != '') ? $common->dbDateFormat($recency['sampleReceiptDate']) : null,
                            'received_specimen_type' => $recency['receivedSpecimenType'],
                            'facility_id' => ($recency['facilityId'] != null) ? base64_decode($recency['facilityId']) : null,
                            'testing_facility_id' => $recency['testingFacility'],
                            'control_line' => $recency['ctrlLine'],
                            'positive_verification_line' => $recency['positiveLine'],
                            'long_term_verification_line' => $recency['longTermLine'],
                            'gender' => $recency['gender'],
                            'latitude' => $recency['latitude'],
                            'longitude' => $recency['longitude'],
                            'age_not_reported' => (isset($recency['ageNotReported']) && $recency['ageNotReported'] != '') ? $recency['ageNotReported'] : no,
                            'age' => ($recency['age'] != '') ? $recency['age'] : null,
                            'marital_status' => $recency['maritalStatus'],
                            'residence' => $recency['residence'],
                            'education_level' => $recency['educationLevel'],
                            'risk_population' => $recency['riskPopulation'],
                            //'other_risk_population' => $recency['otherriskPopulation'],
                            'term_outcome' => $recency['recencyOutcome'],
                            'recency_test_performed' => (isset($recency['testNotPerformed'])) ? $recency['testNotPerformed'] : null,
                            'recency_test_not_performed' => (isset($recency['testNotPerformed']) && $recency['testNotPerformed'] == 'true') ? $recency['recencyreason'] : null,
                            'other_recency_test_not_performed' => (isset($recency['recencyreason']) && $recency['recencyreason'] = 'other') ? $recency['otherreason'] : null,
                            'pregnancy_status' => $recency['pregnancyStatus'],
                            'current_sexual_partner' => $recency['currentSexualPartner'],
                            'past_hiv_testing' => $recency['pastHivTesting'],
                            'last_hiv_status' => $recency['lastHivStatus'],
                            'patient_on_art' => $recency['patientOnArt'],
                            'test_last_12_month' => $recency['testLast12Month'],
                            'location_one' => $recency['location_one'],
                            'location_two' => $recency['location_two'],
                            'location_three' => $recency['location_three'],
                            'added_on' => date('Y-m-d H:i:s'),
                            'added_by' => $recency['addedBy'],
                            'sync_by' => $userId,
                            'exp_violence_last_12_month' => $recency['violenceLast12Month'],
                            'mac_no' => $recency['macAddress'],
                            'cell_phone_number' => $recency['phoneNumber'],
                            //'ip_address'=>$recency[''],
                            'notes' => $recency['notes'],
                            'form_initiation_datetime' => $recency['formInitDateTime'],
                            'app_version' => $recency['appVersion'],
                            'form_transfer_datetime' => $recency['formTransferDateTime'],
                            'form_saved_datetime' => $recency['formSavedDateTime'],

                            'kit_lot_no' => $recency['testKitLotNo'],
                            //'kit_name' => $recency['testKitName'],
                            'tester_name' => $recency['testerName'],
                            'unique_id' => isset($recency['unique_id']) ? $recency['unique_id'] : $this->randomizer(10),
                            'testing_facility_type' => $recency['testingModality'],
                            //'vl_test_date'=>$recency['vlTestDate'],

                            // 'vl_result'=>$recency['vlLoadResult'],

                        );
                        if (isset($recency['invalidControlLine']) && $recency['invalidControlLine'] != '') {
                            $data['invalid_control_line']       = $recency['invalidControlLine'];
                            $data['invalid_verification_line']  = $recency['invalidPositiveLine'];
                            $data['invalid_longterm_line']      = $recency['invalidLongTermLine'];
                        }
                        if ($recency['vlLoadResult'] != '') {
                            $data['vl_result'] = htmlentities($recency['vlLoadResult']);
                            $date['vl_result_entry_date'] = $recency['formSavedDateTime'];
                        }
                        if ($recency['finalOutcome'] != '') {
                            $data['final_outcome'] = $recency['finalOutcome'];
                            $data['final_outcome_updated_by']       = $recency['addedBy'];
                            $data['final_outcome_updated_on']       = $common->getDateTime();
                        }

                        if (isset($recency['vlTestDate']) && trim($recency['vlTestDate']) != "") {
                            $data['vl_test_date'] = $common->dbDateFormat($recency['vlTestDate']);
                        }

                        if (isset($recency['hivDiagnosisDate']) && trim($recency['hivDiagnosisDate']) != "") {
                            $data['hiv_diagnosis_date'] = $common->dbDateFormat($recency['hivDiagnosisDate']);
                        }
                        if (isset($recency['hivRecencyTestDate']) && trim($recency['hivRecencyTestDate']) != "") {
                            $data['hiv_recency_test_date'] = $common->dbDateFormat($recency['hivRecencyTestDate']);
                        }
                        if (isset($recency['dob']) && trim($recency['dob']) != "") {
                            $data['dob'] = $common->dbDateFormat($recency['dob']);
                        }
                        if (isset($recency['testKitExpDate']) && trim($recency['testKitExpDate']) != "") {
                            $data['kit_expiry_date'] = $common->dbDateFormat($recency['testKitExpDate']);
                        }

                        if (isset($params['recencyOutcome']) && $params['recencyOutcome'] != "") {
                            $data['assay_outcome_updated_by']       = $recency['addedBy'];
                            $data['assay_outcome_updated_on']       = $common->getDateTime();
                        }

                        //    if (strpos($recency['recencyOutcome'], 'Long Term') !== false)
                        //    {
                        //         $data['final_outcome'] = 'Long Term';
                        //    }else if (strpos($recency['recencyOutcome'], 'Invalid') !== false)
                        //    {
                        //         $data['final_outcome'] = 'Invalid';
                        //    }else if (strpos($recency['recencyOutcome'], 'Negative') !== false)
                        //    {
                        //         $data['final_outcome'] = 'Assay Negative';
                        //    }
                        $this->insert($data);
                        $lastInsertedId = $this->lastInsertValue;
                        if ($lastInsertedId > 0) {
                            $adapter->commit();
                            $patient = $recency['patientId'];
                            $response['syncData']['response'][$i] = 'success';
                            $response['syncCount']['response'][0]['Total'] = $arrayCount;
                        } else {
                            $adapter->rollBack();
                            $response['syncData']['response'][$i] = 'failed';
                            $response['syncCount']['response'][0]['Total'] = 0;
                        }
                    }
                } catch (Exception $exc) {
                    $adapter->rollBack();
                    error_log($exc->getMessage());
                    error_log($exc->getTraceAsString());
                }
                $i++;
            }
        }
        return $response;
    }

    public function fetchRecencyOrderDetails($id)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName' => 'facility_name'))
            ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'), 'left')
            ->join(array('pr' => 'province_details'), 'pr.province_id = f.province', array('province_name'), 'left')
            ->join(array('dt' => 'district_details'), 'dt.district_id = f.district', array('district_name'), 'left')
            ->join(array('cd' => 'city_details'), 'cd.city_id = f.city', array('city_name'), 'left')
            ->where(array('recency_id' => $id));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }
    public function getTotalSyncCount($syncedBy)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $query = $sql->select()->from(array('r' => 'recency'))
            ->columns(array("Total" => new Expression('COUNT(*)')))
            ->where(array('sync_by' => $syncedBy));
        $queryStr = $sql->buildSqlString($query);
        $result = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $result;
    }

    public function getActiveTester($strSearch)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array('tester_name'))
            ->where('(tester_name like "%' . $strSearch . '%" OR tester_name like "%' . $strSearch . '%")')
            ->limit('100');

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        // \Zend\Debug\Debug::dump($result);die;

        $echoResult = array();
        foreach ($rResult as $row) {
            $echoResult[] = ucwords($row['tester_name']);
        }

        if (count($echoResult) == 0) {
            $echoResult[] = $strSearch;
        }
        // return array("result" => $echoResult);
        return $echoResult;
    }

    public function fetchTesterNameAllDetailsApi()
    {
        $recencyResultObject = '';
        $recencyResultObject = $this->select()->toArray();
        $testerNameList = [];
        foreach ($recencyResultObject as $recencyTestResult) {
            $testerNameList[] = $recencyTestResult['tester_name'];
        }

        $response['status'] = 'success';
        $response['config'] = $testerNameList;
        return $response;
    }

    public function fetchSampleData($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome','facility_id'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where(array('r.term_outcome' => 'Assay Recent'));

        if (isset($params['province']) && $params['province'] != '') {
            $sQuery = $sQuery->where(array('f.province' => $params['province']));
        }
        if (isset($params['district']) && $params['district'] != '') {
            $sQuery = $sQuery->where(array('f.district' => $params['district']));
        }
        if (isset($params['city']) && $params['city'] != '' && $params['city'] != 'other') {
            $sQuery = $sQuery->where(array('f.city' => $params['city']));
        }
        if (isset($params['facility']) && $params['facility'] != '') {
            $sQuery = $sQuery->where(array('r.facility_id' => base64_decode($params['facility'])));
            //$sQuery = $sQuery->where(array('r.vl_test_date' => $params['vlTestDate']));
        }
        if (isset($params['onloadData']) && $params['onloadData'] == 'yes') {
            $sQuery = $sQuery->where(array('(r.vl_result is null OR r.vl_result="")'));
        }
        if ($this->sessionLogin->facilityMap != null && $params['facility'] == '') {
            $sQuery = $sQuery->where('r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . ')');
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult['withTermOutcome'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $sQueryTerm = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'vl_lab', 'vl_request_sent_date_time', 'vl_test_date', 'vl_request_sent', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where('(r.vl_result is null OR r.vl_result="")')
            ->where('r.vl_request_sent != "no"');

        if (isset($params['province']) && $params['province'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('f.province' => $params['province']));
        }
        if (isset($params['district']) && $params['district'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('f.district' => $params['district']));
        }
        if (isset($params['city']) && $params['city'] != '' && $params['city'] != 'other') {
            $sQueryTerm = $sQueryTerm->where(array('f.city' => $params['city']));
        }
        if (isset($params['facility']) && $params['facility'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('r.facility_id' => base64_decode($params['facility'])));
            //$sQueryTerm = $sQueryTerm->where(array('r.vl_test_date' => $params['vlTestDate']));
        }
        if ($sessionLogin->facilityMap != null && $params['facility'] == '') {
            $sQueryTerm = $sQueryTerm->where('r.facility_id IN (' . $sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $sessionLogin->facilityMap . ')');
        }
        $sQueryStrTerm = $sql->buildSqlString($sQueryTerm);
        $rResult['withOutTermOutcome'] = $dbAdapter->query($sQueryStrTerm, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $rResult;
    }

    public function updateVlSampleResult($params)
    {
        //\Zend\Debug\Debug::dump($params);die;
        $logincontainer = new Container('credo');
        $common = new CommonService();
        $sampleVlResult = explode(",", $params['vlResult']);
        $sampleVlResultId = explode(",", $params['vlResultRowId']);
        $dataOutcome = explode(",", $params['vlDataOutCome']);

        foreach ($sampleVlResult as $key => $result) {

            $data = array(
                'vl_result' => $result,
                'vl_test_date' => $common->dbDateFormat($params['vlTestDate'][$key]),
                'vl_result_entry_date' => date('Y-m-d H:i:s'),
            );

            if ((in_array(strtolower($result), $this->vlFailOptionArray))) {
                $data['final_outcome'] = 'Inconclusive';
            } else if ((in_array(strtolower($result), $this->vlResultOptionArray))) {
                $data['final_outcome'] = 'Long Term';
            } else if ($result > 1000) {
                $data['final_outcome'] = 'RITA Recent';
            } else if ($result <= 1000) {
                $data['final_outcome'] = 'Long Term';
            }
            if (isset($data['final_outcome']) && $data['final_outcome'] != "") {
                $data['final_outcome_updated_by']       = $logincontainer->userId;
                $data['final_outcome_updated_on']       = $common->getDateTime();
            }
            if ($logincontainer->roleCode == 'remote_order_user') {
                $data['remote_order']       = 'yes';
            }

            $this->update($data, array('recency_id' => str_replace('vlResultOption', '', $sampleVlResultId[$key])));
        }
    }

    public function fetchAllRecencyResultWithVlList($parameters, $acl)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $common = new CommonService();

        $aColumns = array('r.sample_id', 'f.facility_name', 'DATE_FORMAT(r.hiv_recency_test_date,"%d-%b-%Y")', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'DATE_FORMAT(r.sample_collection_date,"%d-%b-%Y")', 'DATE_FORMAT(r.sample_receipt_date,"%d-%b-%Y")', 'r.received_specimen_type', 'ft.facility_name', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")');
        $orderColumns = array('r.sample_id', 'f.facility_name', 'r.hiv_recency_test_date', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'r.sample_collection_date', 'r.sample_receipt_date', 'r.received_specimen_type', 'ft.facility_name', 'r.vl_test_date');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        $sQuery = $sql->select()->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'control_line', 'positive_verification_line', 'long_term_verification_line', 'age', 'gender', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('st' => 'r_sample_types'), 'st.sample_id = r.received_specimen_type', array('sample_name'), 'left')
            ->where(array(new \Laminas\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('r.location_one' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('r.location_two' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('r.location_three' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if ($this->sessionLogin->facilityMap != null && $parameters['fName'] == '') {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }
        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        $queryContainer->exportRecentResultDataQuery = $sQuery;

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        $aResultFilterTotal = $dbAdapter->query("SELECT FOUND_ROWS() as `totalCount`", $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        $sessionLogin = new Container('credo');
        $roleCode = $sessionLogin->roleCode;
        if ($acl->isAllowed($roleCode, 'Application\Controller\RecencyController', 'generate-pdf')) {
            $update = true;
        } else {
            $update = false;
        }
        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['sample_id'];
            $row[] = $aRow['facility_name'];
            $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
            $row[] = ucwords($aRow['control_line']);
            $row[] = ucwords($aRow['positive_verification_line']);
            $row[] = ucwords($aRow['long_term_verification_line']);
            $row[] = $aRow['term_outcome'];
            $row[] = $aRow['vl_result'];
            $row[] = $aRow['final_outcome'];
            $row[] = ucwords($aRow['gender']);
            $row[] = $aRow['age'];
            $row[] = $common->humanDateFormat($aRow['sample_collection_date']);
            $row[] = $common->humanDateFormat($aRow['sample_receipt_date']);
            $row[] = $aRow['sample_name'];
            $row[] = ucwords($aRow['testing_facility_name']);
            $row[] = $common->humanDateFormat($aRow['vl_test_date']);
            if ($update) {
                $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                         <a class="btn btn-primary" href="javascript:void(0)" onclick="generateRecentPdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>
                         </div>';
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchAllLtResult($parameters, $acl)
    {

        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $common = new CommonService();

        $aColumns = array('r.sample_id', 'f.facility_name', 'DATE_FORMAT(r.hiv_recency_test_date,"%d-%b-%Y")', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'ft.facility_name', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")');
        $orderColumns = array('r.sample_id', 'f.facility_name', 'r.hiv_recency_test_date', 'r.control_line', 'r.positive_verification_line', 'r.long_term_verification_line', 'r.term_outcome', 'r.vl_result', 'r.final_outcome', 'r.gender', 'r.age', 'ft.facility_name', 'r.vl_test_date');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $sQuery = $sql->select()->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'control_line', 'positive_verification_line', 'long_term_verification_line', 'age', 'gender', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array(new \Laminas\Db\Sql\Predicate\Like('final_outcome', '%Long Term%')));

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('r.location_one' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('r.location_two' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('r.location_three' => $parameters['locationThree']));
            }
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if ($this->sessionLogin->facilityMap != null && $parameters['fName'] == '') {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }
        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        $queryContainer->exportLongtermDataQuery = $sQuery;

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        $aResultFilterTotal = $dbAdapter->query("SELECT FOUND_ROWS() as `totalCount`", $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );
        $sessionLogin = new Container('credo');
        $roleCode = $sessionLogin->roleCode;
        if ($acl->isAllowed($roleCode, 'Application\Controller\RecencyController', 'generate-pdf')) {
            $update = true;
        } else {
            $update = false;
        }

        foreach ($rResult as $aRow) {

            $row = array();
            $row[] = $aRow['sample_id'];
            $row[] = $aRow['facility_name'];
            $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
            $row[] = ucwords($aRow['control_line']);
            $row[] = ucwords($aRow['positive_verification_line']);
            $row[] = ucwords($aRow['long_term_verification_line']);
            $row[] = $aRow['term_outcome'];
            $row[] = $aRow['vl_result'];
            $row[] = $aRow['final_outcome'];
            $row[] = ucwords($aRow['gender']);
            $row[] = $aRow['age'];
            $row[] = ucwords($aRow['testing_facility_name']);
            $row[] = $common->humanDateFormat($aRow['vl_test_date']);
            if ($update) {
                $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                <a class="btn btn-primary" href="javascript:void(0)" onclick="generateLTermPdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>
                </div>';
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchTatReportAPI($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $common = new CommonService();

        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status', 'secret_key'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->buildSqlString($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $secretKey = $uResult['secret_key'];
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $sQuery = $sql->select()->from(array('r' => 'recency'))
                ->columns(array(
                    'sample_id', 'final_outcome', "hiv_recency_test_date" => new Expression("DATE_FORMAT(DATE(hiv_recency_test_date), '%d-%b-%Y')"), 'vl_test_date' => new Expression("DATE_FORMAT(DATE(vl_test_date), '%d-%b-%Y')"), 'vl_result_entry_date' => new Expression("DATE_FORMAT(DATE(vl_result_entry_date), '%d-%b-%Y')"),
                    "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_test_date))) AS DECIMAL (10))"),
                ))
                ->where(array('vl_result_entry_date not like "" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_test_date not like "" AND vl_test_date not like ""'))
                ->group('recency_id');
            if (isset($params['start']) && isset($params['end'])) {
                $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) . "'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end'])) . "'"));
            }
            if ($uResult['role_code'] != 'admin') {
                $sQuery = $sQuery->where(array('u.auth_token' => $params['authToken']));
            }
            if (isset($params['hivRecencyTest']) && trim($params['hivRecencyTest']) != '') {
                $s_c_date = explode("to", $_POST['hivRecencyTest']);
                if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                    $start_date = $common->dbDateFormat(trim($s_c_date[0]));
                }
                if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                    $end_date = $common->dbDateFormat(trim($s_c_date[1]));
                }
            }

            if ($params['hivRecencyTest'] != '') {
                $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
            }
            $sQueryStr = $sql->buildSqlString($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($rResult) > 0) {
                $response['status'] = 'success';
                if ($params['version'] > 2.8 && $secretKey != "" && $params["version"] != null) {
                    $response['tat'] = $this->cryptoJsAesEncrypt($secretKey, $rResult);
                } else
                    $response['tat'] = $rResult;
            } else {
                $response["status"] = "fail";
                $response["message"] = "You don't have TAT data!";
            }
        } else {
            $response["status"] = "fail";
            $response["message"] = "Please check your token credentials!";
        }
        return $response;
    }

    public function fetchTatReport($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $common = new CommonService();

        $aColumns = array('r.sample_id', 'ft.facility_name', 'r.final_outcome', 'DATE_FORMAT(r.hiv_recency_test_date,"%d-%b-%Y")', 'DATE_FORMAT(r.vl_test_date,"%d-%b-%Y")', 'DATE_FORMAT(r.vl_result_entry_date,"%d-%b-%Y")');
        $orderColumns = array('r.sample_id', 'ft.facility_name', 'r.final_outcome', 'r.hiv_recency_test_date', 'r.vl_test_date', 'r.vl_result_entry_date');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
            ->columns(array(
                'sample_id', 'final_outcome', "hiv_recency_test_date", 'vl_test_date', 'vl_result_entry_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type',
                "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_test_date))) AS DECIMAL (10))"),
            ))

            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array('vl_result_entry_date not like "" AND hiv_recency_test_date not like "" AND vl_test_date not like ""'))
            ->group('recency_id');
        // if(isset($params['start']) && isset($params['end'])){
        //     $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) ."'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end']))."'"));
        // }

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }
        if ($parameters['province'] != '') {
            $sQuery->where(array('r.location_one' => $parameters['province']));
        }
        if ($parameters['district'] != '') {
            $sQuery->where(array('r.location_two' => $parameters['district']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => base64_decode($parameters['testingFacility'])));
        }
        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if ($this->sessionLogin->facilityMap != null && $parameters['testing_facility_id'] == '') {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }
        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        $queryContainer->exportTatQuery = $sQuery;

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        $aResultFilterTotal = $dbAdapter->query("SELECT FOUND_ROWS() as `totalCount`", $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = $aRow['sample_id'];
            $row[] = $aRow['testing_facility_name'];
            $row[] = $aRow['final_outcome'];
            $row[] = $common->humanDateFormat($aRow['hiv_recency_test_date']);
            $row[] = $common->humanDateFormat($aRow['vl_test_date']);
            if (isset($aRow['vl_result_entry_date']) && trim($aRow['vl_result_entry_date']) != '' && $aRow['vl_result_entry_date'] != '0000-00-00 00:00:00')
                $row[] = date('d-M-Y', strtotime($aRow['vl_result_entry_date']));
            else
                $row[] = '';
            $row[] = $aRow['diffInDays'];
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchSampleResult($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $common = new CommonService();

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where(array('vl_result!="" AND vl_result is not null AND mail_sent_status is null'));
        if ($params['locationOne'] != '') {
            $sQuery = $sQuery->where(array('province' => $params['locationOne']));
            if ($params['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('district' => $params['locationTwo']));
            }
            if ($params['locationThree'] != '' && $params['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('city' => $params['locationThree']));
            }
        }
        if ($params['facilityId'] != '') {
            $sQuery = $sQuery->where(array('r.facility_id' => base64_decode($params['facilityId'])));
        }
        if (isset($params['hivRecencyTest']) && trim($params['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($params['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if ($this->sessionLogin->facilityMap != null && $params['facilityId'] == '') {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function fetchEmailSendResult($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'))
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'))
            ->join(array('st' => 'r_sample_types'), 'st.sample_id = r.received_specimen_type', array('sample_name'))
            ->where("recency_id IN(" . $params['selectedSampleId'] . ")");

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateEmailSendResult($params, $configResult)
    {
        $tempDb = new \Application\Model\TempMailTable($this->adapter);

        $emailFormField = json_decode($params['emailResultFields'], true);
        $to = $emailFormField['toEmail'];
        $subject = $emailFormField['subject'];
        $message = $emailFormField['message'];
        $fromName = 'HIV Recency Testing';
        $attachment = $params['pdfFile'];
        $fromMail = $configResult["email"]["config"]["username"];
        $mailResult = 0;
        $mailResult = $tempDb->insertTempMailDetails($to, $subject, $message, $fromMail, $fromName, $cc = null, $bcc = null, $attachment);
        if ($mailResult > 0) {
            if(!empty($emailFormField['selectedSampleId'])){
                $recencyIds = explode(",", $emailFormField['selectedSampleId']);
                foreach ($recencyIds as $recencyId) {
                    $this->update(array('mail_sent_status' => 'yes'), array('recency_id' => $recencyId));
                }
            }
        }
        return $mailResult;
    }

    public function updateOutcome()
    {
        //first check termoutcome avialable or not
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array('recency_id', 'control_line', 'positive_verification_line', 'long_term_verification_line'))
            ->where(array('control_line!="" AND positive_verification_line!="" AND long_term_verification_line!="" AND (term_outcome="" OR term_outcome IS NULL)'));

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        //update assay outcome
        if (count($rResult) > 0) {
            foreach ($rResult as $outcome) {
                $this->updateTermOutcome($outcome);
            }
        }

        //second check final outcome
        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array('recency_id', 'term_outcome', 'vl_result'))
            ->where(array('vl_result!="" AND term_outcome="Assay Recent" AND (final_outcome="" OR final_outcome IS NULL)'));

        $fQueryStr = $sql->buildSqlString($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if (count($fResult) > 0) {
            foreach ($fResult as $fOutCome) {
                $this->updateFinalOutcome($fOutCome);
            }
        }
    }

    //refer updateOutcome Function
    public function updateFinalOutcome($fOutCome)
    {
        $recencyId = $fOutCome['recency_id'];
        $logincontainer = new Container('credo');
        if ((in_array(strtolower($fOutCome['vl_result']), $this->vlFailOptionArray))) {
            $data['final_outcome'] = 'Inconclusive';
        } else if ((in_array(strtolower($fOutCome['vl_result']), $this->vlResultOptionArray))) {
            $data['final_outcome'] = 'Long Term';
        } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] > 1000) {
            $data['final_outcome'] = 'RITA Recent';
        } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] <= 1000) {
            $data['final_outcome'] = 'Long Term';
        }
        if (isset($data['final_outcome']) && $data['final_outcome'] != "") {
            $data['final_outcome_updated_by']       = $logincontainer->userId;
            $data['final_outcome_updated_on']       = $common->getDateTime();
        }
        if ($logincontainer->roleCode == 'remote_order_user') {
            $data['remote_order']       = 'yes';
        }
        $this->update($data, array('recency_id' => $recencyId));
    }

    //refer updateOutcome Function
    public function updateTermOutcome($outcome)
    {
        $common = new CommonService();
        $logincontainer = new Container('credo');
        $controlLine = $outcome['control_line'];
        $positiveControlLine = $outcome['positive_verification_line'];
        $longControlLine = $outcome['long_term_verification_line'];
        $recencyId = $outcome['recency_id'];
        if (($controlLine == 'absent' && $positiveControlLine == 'absent' && $longControlLine == 'absent')
            || ($controlLine == 'absent' && $positiveControlLine == 'absent' && $longControlLine == 'present')
            || ($controlLine == 'absent' && $positiveControlLine == 'present' && $longControlLine == 'absent')
            || ($controlLine == 'absent' && $positiveControlLine == 'present' && $longControlLine == 'present')
            || ($controlLine == 'present' && $positiveControlLine == 'absent' && $longControlLine == 'present')
        ) {
            $data = array('term_outcome' => 'Invalid â€“ Please Verify');
        } else if ($controlLine == 'present' && $positiveControlLine == 'absent' && $longControlLine == 'absent') {
            $data = array('term_outcome' => 'Assay Negative');
        } else if ($controlLine == 'present' && $positiveControlLine == 'present' && $longControlLine == 'absent') {
            $data = array('term_outcome' => 'Assay Recent');
        } else if ($controlLine == 'present' && $positiveControlLine == 'present' && $longControlLine == 'present') {
            $data = array('term_outcome' => 'Long Term');
        }
        if (isset($data['term_outcome']) && $data['term_outcome'] != "") {
            $data['assay_outcome_updated_by']       = $logincontainer->userId;
            $data['assay_outcome_updated_on']       = $common->getDateTime();
        }
        if ($logincontainer->roleCode == 'remote_order_user') {
            $data['remote_order']       = 'yes';
        }
        $this->update($data, array('recency_id' => $recencyId));
    }

    public function vlsmSync($sm)
    {
        $dbTwoAdapter = $sm->get('db1');
        $sql1 = new Sql($dbTwoAdapter);

        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array('recency_id', 'term_outcome', 'vl_result', 'sample_id'))
            ->where(array('(vl_result="" OR vl_result IS NULL)  AND term_outcome="Assay Recent"'));

        $fQueryStr = $sql->buildSqlString($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if (count($fResult) > 0) {
            foreach ($fResult as $data) {
                $fQuery = $sql1->select()->from(array('vl' => 'vl_request_form'))
                    ->columns(array('result', 'sample_code'))
                    ->where(array('sample_code' => $data['sample_id']));

                $fQueryStr = $sql1->buildSqlString($fQuery);
                $fResult = $dbTwoAdapter->query($fQueryStr, $dbTwoAdapter::QUERY_MODE_EXECUTE)->current();

                if (isset($fResult['result']) && $fResult['result'] != '') {
                    $this->update(array('vl_result' => $fResult['result']), array('recency_id' => $data['recency_id']));

                    $this->updateFinalOutcome($data);
                }
            }
        }
    }

    public function getWeeklyReport($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $queryContainer = new Container('query');
        $general = new CommonService();

        if (isset($params['samplesCollectionDate']) && trim($params['samplesCollectionDate']) != '') {
            $s_c_date = explode("to", $_POST['samplesCollectionDate']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        $rQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    "Samples Received" => new Expression("COUNT(*)"),
                    "Samples Collected" => new Expression("SUM(CASE
                                                                                WHEN (r.sample_collection_date is NOT NULL AND r.sample_collection_date not like '') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Samples Pending to be Tested" => new Expression("SUM(CASE
                                                                                WHEN (r.term_outcome is NULL OR r.term_outcome ='') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Samples Tested" => new Expression("SUM(CASE
                                                                                WHEN (r.term_outcome IS NOT NULL && term_outcome !='') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Assay Recent" => new Expression("SUM(CASE
                                                                                    WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                                                                    ELSE 0
                                                                                    END)"),
                    "Long Term" => new Expression("SUM(CASE
                                                                                WHEN (term_outcome='Long Term' OR term_outcome='long term') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Assay Negative" => new Expression("SUM(CASE
                                                                                WHEN (term_outcome='Assay Negative' OR term_outcome='assay negative') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "VL Done" => new Expression("SUM(CASE
                                                                                WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent') AND (vl_result!='' AND vl_result is NOT NULL)) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "VL Pending" => new Expression("SUM(CASE
                                                                                WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent') AND (vl_result='' OR vl_result is NULL)) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "RITA Recent" => new Expression("SUM(CASE
                                                                                WHEN (final_outcome='RITA Recent' OR final_outcome='RITA recent') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Long Term Final" => new Expression("SUM(CASE
                                                                                WHEN (final_outcome='Long Term' OR final_outcome='long term') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Inconclusive" => new Expression("SUM(CASE
                                                                                WHEN (final_outcome='Inconclusive' OR final_outcome='inconclusive') THEN 1
                                                                                ELSE 0
                                                                                END)"),
                )
            );

        if ($params['samplesCollectionDate'] != '') {
            $rQuery = $rQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($params['testingFacility'] != '') {
            $rQuery = $rQuery->where(array('r.testing_facility_id' => $params['testingFacility']));
        }
        if ($params['province'] != '') {
            $rQuery = $rQuery->where(array('r.location_one' => $params['province']));
        }
        if ($params['district'] != '') {
            $rQuery = $rQuery->where(array('r.location_two' => $params['district']));
        }
        if ($params['facilityName'] != '') {
            $rQuery = $rQuery->where(array('r.facility_id' => base64_decode($params['facilityName'])));
        }
        if ($this->sessionLogin->facilityMap != null && $params['facilityName'] == '') {
            $rQuery = $rQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }
        $queryContainer->exportWeeklyDataQuery = $rQuery;
        $rQueryStr = $sql->buildSqlString($rQuery);
        $fResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $fResult;
    }

    public function fetchAllRecencyResult($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $general = new CommonService();
        $aColumns = array('f.facility_name', 'ft.facility_name');
        $orderColumns = array('f.facility_name', 'ft.facility_name', 'totalSamples', 'samplesReceived', 'samplesRejected', 'samplesTestBacklog', 'samplesTestVlPending', 'samplesTestedRecency', 'samplesTestedViralLoad', 'samplesFinalOutcome', 'printedCount', 'samplesFinalLongTerm', '', 'ritaRecent', '', 'samplesFinalInconclusive', '', 'samplesInvalid', '');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
            ->columns(
                array(
                    "totalSamples" => new Expression('COUNT(*)'),
                    "samplesReceived" => new Expression("SUM(CASE
                                                               WHEN (((r.sample_receipt_date is NOT NULL) )) THEN 1
                                                               ELSE 0
                                                               END)"),
                    "samplesRejected" => new Expression("SUM(CASE
                                                                 WHEN (((r.recency_test_not_performed ='sample_rejected') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestedRecency" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome='Assay Recent') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestedViralLoad" => new Expression("SUM(CASE
                                                                 WHEN (( r.term_outcome='Assay Recent' AND (r.vl_result is NOT NULL or r.vl_result != '') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesFinalOutcome" => new Expression("SUM(CASE
                                                                 WHEN (((r.final_outcome is NOT NULL and r.final_outcome != '') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestBacklog" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome is null AND (recency_test_not_performed IS NULL OR recency_test_not_performed ='') ) )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestVlPending" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome='Assay Recent' AND (vl_result='' or vl_result is null)))) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesFinalLongTerm" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='Long Term')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesFinalInconclusive" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome like 'Inconclusive')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesInvalid" => new Expression("SUM(CASE
                                                                 WHEN ((r.control_line like 'absent')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "ritaRecent" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='RITA Recent')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "printedCount" => new Expression("SUM(CASE
                                                                 WHEN ((r.result_printed_on not like '' and r.result_printed_on is not null)) THEN 1
                                                                 ELSE 0
                                                                 END)"),

                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.facility_id');


        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }

            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if (isset($sOrder) && $sOrder != "") {
            if (($sOrder == "ft.facility_name asc") || ($sOrder == "ft.facility_name desc")) {
                $sQuery->order(new Expression("CASE WHEN `testing_facility_name` IS NULL OR `testing_facility_name` = '' THEN 1 ELSE 0 END, " . $sOrder));
            } else {
                $sQuery->order($sOrder);
            }
        }



        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $queryContainer->exportRecencyDataResultDataQuery = $sQuery;

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))

            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.facility_id');

        if ($this->sessionLogin->facilityMap != null) {
            $iQuery = $iQuery->where('r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . ')');
        }
        $iQueryStr = $sql->buildSqlString($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
            "footerData" => array()
        );

        $totalsRow = array();

        foreach ($rResult as $aRow) {

            $ltPercentage = $invalidPercentage = $inconlusivePercentage = $recentPercentage = "0 %";
            if (trim($aRow['samplesFinalLongTerm']) != "") {
                $ltPercentage = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (trim($aRow['ritaRecent']) != "") {
                $recentPercentage = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (isset($aRow['samplesFinalInconclusive']) && !empty($aRow['samplesFinalInconclusive'])) {
                $inconlusivePercentage = round((($aRow['samplesFinalInconclusive'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (isset($aRow['samplesInvalid']) && !empty($aRow['samplesInvalid'])) {
                $invalidPercentage = round((($aRow['samplesInvalid'] / $aRow['samplesTestedRecency']) * 100), 2) . '%';
            }

            $row = array();


            $row[] = $aRow['facility_name'];
            $row[] = $aRow['testing_facility_name'];
            $row[] = $aRow['totalSamples'];
            $row[] = $aRow['samplesReceived'];
            $row[] = $aRow['samplesRejected'];
            $row[] = $aRow['samplesTestBacklog'];
            $row[] = $aRow['samplesTestVlPending'];
            $row[] = $aRow['samplesTestedRecency'];
            $row[] = $aRow['samplesTestedViralLoad'];
            $row[] = $aRow['samplesFinalOutcome'];
            $row[] = $aRow['printedCount'];
            $row[] = $aRow['samplesFinalLongTerm'];
            $row[] = $ltPercentage;
            $row[] = $aRow['ritaRecent'];
            $row[] = $recentPercentage;
            $row[] = $aRow['samplesFinalInconclusive'];
            $row[] = $inconlusivePercentage;
            $row[] = $aRow['samplesInvalid'];
            $row[] = $invalidPercentage;

            $output['aaData'][] = $row;
        }

        foreach ($aResultFilterTotal as $aRow) {

            $row = array();

            $output['footerData'][0] = "";
            $output['footerData'][1] = "Overall Total";
            $output['footerData'][2] += $aRow['totalSamples'];
            $output['footerData'][3] += $aRow['samplesReceived'];
            $output['footerData'][4] += $aRow['samplesRejected'];
            $output['footerData'][5] += $aRow['samplesTestBacklog'];
            $output['footerData'][6] += $aRow['samplesTestVlPending'];
            $output['footerData'][7] += $aRow['samplesTestedRecency'];
            $output['footerData'][8] += $aRow['samplesTestedViralLoad'];
            $output['footerData'][9] += $aRow['samplesFinalOutcome'];
            $output['footerData'][10] += $aRow['printedCount'];
            $output['footerData'][11] += $aRow['samplesFinalLongTerm'];
            $output['footerData'][12] = "";
            $output['footerData'][13] += $aRow['ritaRecent'];
            $output['footerData'][14] = "";
            $output['footerData'][15] += $aRow['samplesFinalInconclusive'];
            $output['footerData'][16] = "";
            $output['footerData'][17] += $aRow['samplesInvalid'];
            $output['footerData'][18] = "";
        }


        if ($output['footerData'][9] > 0) {
            $output['footerData'][12] = round(($output['footerData'][11] / $output['footerData'][9]) * 100, 2) . "%";
        }

        if ($output['footerData'][9] > 0) {
            $output['footerData'][14] = round(($output['footerData'][13] / $output['footerData'][9]) * 100, 2) . "%";
        }

        if ($output['footerData'][9] > 0) {
            $output['footerData'][16] = round(($output['footerData'][15] / $output['footerData'][9]) * 100, 2) . "%";
        }

        if (($output['footerData'][3] - $output['footerData'][5]) > 0) {
            $output['footerData'][18] = round(($output['footerData'][17] / ($output['footerData'][3] - $output['footerData'][5])) * 100, 2) . "%";
        }

        return $output;
    }

    public function fetchRecencyAllDataCount($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    "totalSamples" => new Expression('COUNT(*)'),
                    "samplesTestedRecency" => new Expression("SUM(CASE
                                                           WHEN (((r.term_outcome='Assay Recent') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesTestedViralLoad" => new Expression("SUM(CASE
                                                           WHEN (( r.term_outcome='Assay Recent' AND (r.vl_result is NOT NULL or r.vl_result != '') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesFinalOutcome" => new Expression("SUM(CASE
                                                           WHEN (((r.final_outcome like 'RITA Recent') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesFinalOutcomeLT" => new Expression("SUM(CASE
                                                           WHEN (((r.final_outcome = 'Long Term') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesTestRejected" => new Expression("SUM(CASE
                                                           WHEN (((r.recency_test_not_performed IS NOT NULL AND r.recency_test_not_performed != '') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesPosVerificationAbsent" => new Expression("SUM(CASE
                                                           WHEN (((r.positive_verification_line='absent') )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesTestBacklog" => new Expression("SUM(CASE
                                                           WHEN (((r.term_outcome is null AND (recency_test_not_performed IS NULL OR recency_test_not_performed ='') ) )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "samplesNotTestedViralLoad" => new Expression("SUM(CASE
                                                           WHEN (((r.term_outcome='Assay Recent' AND (vl_result is null or vl_result ='')) )) THEN 1
                                                           ELSE 0
                                                           END)"),
                    "assayLongTerm" => new Expression("SUM(CASE
                                                           WHEN (r.term_outcome = 'Long Term') THEN 1
                                                           ELSE 0
                                                           END)"),
                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left');

        //    if($parameters['fName']!=''){
        //        $sQuery->where(array('r.facility_id'=>$parameters['fName']));
        //    }
        //    if($parameters['testingFacility']!=''){
        //        $sQuery->where(array('r.testing_facility_id'=>$parameters['testingFacility']));
        //    }
        //    if($parameters['locationOne']!=''){
        //        $sQuery = $sQuery->where(array('p.province_id'=>$parameters['locationOne']));
        //        if($parameters['locationTwo']!=''){
        //              $sQuery = $sQuery->where(array('d.district_id'=>$parameters['locationTwo']));
        //        }
        //        if($parameters['locationThree']!=''){
        //              $sQuery = $sQuery->where(array('c.city_id'=>$parameters['locationThree']));
        //        }
        //     }
        //        if(isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates'])!= ''){
        //             $s_c_date = explode("to", $_POST['sampleTestedDates']);
        //             if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
        //                  $start_date = $general->dbDateFormat(trim($s_c_date[0]));
        //             }
        //             if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
        //                  $end_date = $general->dbDateFormat(trim($s_c_date[1]));
        //             }
        //        }

        //        if($parameters['sampleTestedDates']!=''){
        //             $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date ."'", "r.sample_collection_date <='" . $end_date."'"));
        //        }
        //        if($parameters['tOutcome']!=''){
        //             $sQuery->where(array('term_outcome'=>$parameters['tOutcome']));
        //         }

        //         if($parameters['finalOutcome']!=''){
        //             $sQuery->where(array('final_outcome'=>$parameters['finalOutcome']));
        //         }



        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function fetchFinalOutcomeChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sessionLogin = new Container('credo');

        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';

        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'));

        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "week" => new Expression("WEEKOFYEAR(added_on)"),
                        "monthyear" => new Expression("DATE_FORMAT(added_on, '%Y')"),
                        "ritaRecent" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'RITA Recent') THEN 1 ELSE 0 END) / COUNT(*))* 100"),
                        "longTerm" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Long Term') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "inconclusive" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "week" => new Expression("WEEKOFYEAR(added_on)"),
                        "monthyear" => new Expression("DATE_FORMAT(added_on, '%Y')"),
                        "ritaRecent" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'RITA Recent') THEN 1 ELSE 0 END))"),
                        "longTerm" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Long Term') THEN 1 ELSE 0 END))"),
                        "inconclusive" => new Expression("SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END)"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            //->group(array(new Expression('YEAR(hiv_recency_test_date)'),new Expression('MONTH(hiv_recency_test_date)')))
            ->group(array(new Expression('WEEKOFYEAR(sample_collection_date)')));

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }

            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if (isset($parameters['ritaFilter']) && trim($parameters['ritaFilter']) != '') {
            if ($parameters['ritaFilter'] == 'inconclusive') {
                $sQuery = $sQuery->order("inconclusive DESC");
            } else if ($parameters['ritaFilter'] == 'longTerm') {
                $sQuery = $sQuery->order("longTerm DESC");
            } else if ($parameters['ritaFilter'] == 'ritaRecent') {
                $sQuery = $sQuery->order("ritaRecent DESC");
            } else if ($parameters['ritaFilter'] == 'hivRecencyTestDate') {
                $sQuery = $sQuery->order("hiv_recency_test_date DESC");
            }
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            $weekDateOfMonth = $this->getStartAndEndDate($sRow["week"], $sRow['monthyear']);
            if ($sRow["week"] == null) {
                continue;
            }

            $result['finalOutCome']['RITA Recent'][$j] = (isset($sRow['ritaRecent']) && $sRow['ritaRecent'] != null) ? round($sRow['ritaRecent'], 2) : 0;
            $result['finalOutCome']['Long Term'][$j] = (isset($sRow['longTerm']) && $sRow['longTerm'] != null) ? round($sRow['longTerm'], 2) : 0;
            $result['finalOutCome']['Inconclusive'][$j] = (isset($sRow['inconclusive']) && $sRow['inconclusive'] != null) ? round($sRow['inconclusive'], 2) : 0;
            $n = $sRow['total'];

            $result['date'][$j] = $weekDateOfMonth[0] . " to " . $weekDateOfMonth[1] . "<br>(N=$n)";

            //$result['total']+=(isset($sRow['total']) && $sRow['total'] != NULL) ? $sRow['total'] : 0;
            $result['total'] += $n;
            $j++;
        }

        return $result;
    }

    public function getStartAndEndDate($week, $year)
    {

        $dates[0] = date("d-M-y", strtotime($year . 'W' . str_pad($week, 2, 0, STR_PAD_LEFT)));
        $dates[1] = date("d-M-y", strtotime($year . 'W' . str_pad($week, 2, 0, STR_PAD_LEFT) . ' +6 days'));
        return $dates;
    }

    public function fetchRecencyLabActivityChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';

        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "samplesCollected" => new Expression("COUNT(*)"),
                        "samplesTested" => new Expression("(SUM(CASE
                                                                    WHEN (((r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date not like ''))) THEN 1
                                                                    ELSE 0
                                                                    END) / COUNT(*) )*100"),
                        "VLDone" => new Expression("(SUM(CASE
                                                                    WHEN ((vl_result!='' AND vl_result is NOT NULL)) THEN 1
                                                                    ELSE 0
                                                                    END) / COUNT(*)) * 100"),

                        "VLPending" => new Expression("(SUM(CASE
                                                                    WHEN (((r.term_outcome='Assay Recent' AND (vl_result='' or vl_result is null)))) THEN 1
                                                                    ELSE 0
                                                                    END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "samplesCollected" => new Expression("SUM(CASE
                                                                            WHEN (((r.added_on is NOT NULL AND r.added_on not like ''))) THEN 1
                                                                            ELSE 0
                                                                        END)"),
                        "samplesTested" => new Expression("SUM(CASE
                                                                        WHEN (((r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date not like  ''))) THEN 1
                                                                        ELSE 0
                                                                        END)"),
                        "VLDone" => new Expression("SUM(CASE
                                                                        WHEN ((vl_result!='' AND vl_result is NOT NULL)) THEN 1
                                                                        ELSE 0
                                                                        END)"),

                        "VLPending" => new Expression("SUM(CASE
                                                                        WHEN (((r.term_outcome='Assay Recent' AND (vl_result='' or vl_result is null)))) THEN 1
                                                                        ELSE 0
                                                                        END)"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group("testing_facility_name");

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }

            $sQuery = $sQuery->where(array("r.added_on >='" . $start_date . "'", "r.added_on <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        if (isset($parameters['activityFilter']) && trim($parameters['activityFilter']) != '') {
            if ($parameters['activityFilter'] == 'recencyDone') {
                $sQuery = $sQuery->order("samplesTested DESC");
            } else if ($parameters['activityFilter'] == 'vlDone') {
                $sQuery = $sQuery->order("VLDone DESC");
            } else if ($parameters['activityFilter'] == 'vlPending') {
                $sQuery = $sQuery->order("VLPending DESC");
            }
        } else {
            $sQuery = $sQuery->order("samplesCollected DESC");
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //echo($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;

        $result = array();
        $result['format'] = $format;

        foreach ($rResult as $sRow) {
            if ($sRow["testing_facility_name"] == null) {
                $sRow["testing_facility_name"] = 'Testing Facility Not Recorded';
            }

            $n = (isset($sRow['samplesCollected']) && $sRow['samplesCollected'] != null) ? round($sRow['samplesCollected'], 2) : 0;
            $result['labActivity']['Recency Test Done'][$j] = (isset($sRow['samplesTested']) && $sRow['samplesTested'] != null) ? round($sRow['samplesTested'], 2) : 0;
            $result['labActivity']['VL Test Done'][$j] = (isset($sRow['VLDone']) && $sRow['VLDone'] != null) ? round($sRow['VLDone'], 2) : 0;
            $result['labActivity']['VL Test Pending'][$j] = (isset($sRow['VLPending']) && $sRow['VLPending'] != null) ? round($sRow['VLPending'], 2) : 0;
            $result['date'][$j] = $sRow["testing_facility_name"] . "<br>(N=$n)";

            $result['total'] += $n;

            $j++;
        }

        return $result;
    }

    public function fetchTesterWiseFinalOutcomeChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    'tester_name',
                    "totalSamples" => new Expression('COUNT(*)'),
                    "assayRecent" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                             ELSE 0
                                             END)"),
                    "assayLongTerm" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Long Term' OR term_outcome ='long term')) THEN 1
                                             ELSE 0
                                             END)"),
                    "assayInvalid" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Invalid' OR term_outcome ='invalid')) THEN 1
                                             ELSE 0
                                             END)"),
                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('tester_name');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if (isset($parameters['recencyTesterFilter']) && trim($parameters['recencyTesterFilter']) != '') {
            if ($parameters['recencyTesterFilter'] == 'assayRecent') {
                $sQuery = $sQuery->order("assayRecent DESC");
            } else if ($parameters['recencyTesterFilter'] == 'assayLongTerm') {
                $sQuery = $sQuery->order("assayLongTerm DESC");
            } else if ($parameters['recencyTesterFilter'] == 'assayInvalid') {
                $sQuery = $sQuery->order("assayInvalid DESC");
            }
        } else {
            $sQuery = $sQuery->order("totalSamples DESC");
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        $result['total'] = 0;
        foreach ($rResult as $sRow) {
            if ($sRow["tester_name"] == null) {
                continue;
            }

            $result['finalOutCome']['Total'][$j] = (isset($sRow['totalSamples']) && $sRow['totalSamples'] != null) ? $sRow['totalSamples'] : 0;
            $result['finalOutCome']['Assay Long Term'][$j] = (isset($sRow['assayLongTerm']) && $sRow['assayLongTerm'] != null) ? $sRow['assayLongTerm'] : 0;
            $result['finalOutCome']['Assay Recent'][$j] = (isset($sRow['assayRecent']) && $sRow['assayRecent'] != null) ? $sRow['assayRecent'] : 0;
            $result['finalOutCome']['Assay Invaild'][$j] = (isset($sRow['assayInvalid']) && $sRow['assayInvalid'] != null) ? $sRow['assayInvalid'] : 0;
            $result['testerName'][$j] = $sRow['tester_name'];

            $result['total'] += $result['finalOutCome']['Total'][$j];

            $j++;
        }

        return $result;
    }

    public function fetchTesterWiseInvalidChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    'tester_name',
                    "inconclusive" => new Expression("SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END)"),

                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('tester_name');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        //$result['total']=0;
        foreach ($rResult as $sRow) {
            if ($sRow["tester_name"] == null || $sRow['inconclusive'] == 0) {
                continue;
            }

            $result['finalOutCome']['Assay Invaild'][$j] = (isset($sRow['inconclusive']) && $sRow['inconclusive'] != null) ? $sRow['inconclusive'] : 0;
            $result['testerName'][$j] = $sRow['tester_name'];
            $result['total'] += $result['finalOutCome']['Assay Invaild'][$j];
            $j++;
        }
        return $result;
    }

    public function fetchFacilityWiseInvalidChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    "inconclusive" => new Expression("SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END)"),
                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('facility_name');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }
        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where("(r.sample_collection_date >='" . $start_date . "' AND r.sample_collection_date<='" . $end_date . "')");
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        foreach ($rResult as $sRow) {
            if ($sRow["facility_name"] == null || $sRow['inconclusive'] == 0) {
                continue;
            }

            $result['fInvalidReport']['Assay Invaild'][$j] = (isset($sRow['inconclusive']) && $sRow['inconclusive'] != null) ? $sRow['inconclusive'] : 0;
            $result['facilityName'][$j] = $sRow['facility_name'];
            $result['total'] += $result['fInvalidReport']['Assay Invaild'][$j];
            $j++;
        }
        return $result;
    }

    public function fetchLotChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array('kit_lot_no', 'kit_expiry_date', "total" => new Expression('COUNT(*)')))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('kit_lot_no');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }
        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where("(r.sample_collection_date >='" . $start_date . "' AND r.sample_collection_date<='" . $end_date . "')");
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        foreach ($rResult as $sRow) {
            $expDate = "";
            if ($sRow["kit_lot_no"] == null) {
                continue;
            }

            if (trim($sRow['kit_expiry_date']) != "") {
                $expDate = $general->humanDateFormat($sRow['kit_expiry_date']);
            }

            $result[$sRow['kit_lot_no'] . " (Exp. Date: " . $expDate . ")"] = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
        }
        return $result;
    }

    public function fetchRecentInfectionByGenderChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();

        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';

        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "gender",
                        "total" => new Expression('COUNT(*)'),
                        "ritaRecent" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'RITA Recent') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "longTerm" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Long Term') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "inconclusive" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END))"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "gender",
                        "total" => new Expression('COUNT(*)'),
                        "ritaRecent" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'RITA Recent') THEN 1 ELSE 0 END))"),
                        "longTerm" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Long Term') THEN 1 ELSE 0 END))"),
                        "inconclusive" => new Expression("(SUM(CASE WHEN (r.final_outcome = 'Inconclusive') THEN 1 ELSE 0 END))"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            //->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !='')")
            ->group('gender');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            if ($sRow["gender"] == null) {
                continue;
            }

            if ($sRow["gender"] == 'not_reported') {
                $sRow["gender"] = 'Gender Missing';
            } else {
                $sRow["gender"] = ucwords($sRow["gender"]);
            }

            $n = (isset($sRow['total']) && $sRow['total'] != null) ? round($sRow['total'], 2) : 0;
            $result['finalOutCome']['Long Term'][$sRow["gender"]] = (isset($sRow['longTerm']) && $sRow['longTerm'] != null && $sRow['longTerm'] > 0) ? round($sRow['longTerm'], 2) : 0;
            $result['finalOutCome']['RITA Recent'][$sRow["gender"]] = (isset($sRow['ritaRecent']) && $sRow['ritaRecent'] != null && $sRow['ritaRecent'] > 0) ? round($sRow['ritaRecent'], 2) : 0;
            $result['finalOutCome']['Inconclusive'][$sRow["gender"]] = (isset($sRow['inconclusive']) && $sRow['inconclusive'] != null && $sRow['inconclusive'] > 0) ? round($sRow['inconclusive'], 2) : 0;

            $result['gender'][$j] = ucwords($sRow["gender"]) . " (N=$n)";
            $result['total'] += $n;
            $j++;
        }
        //\Zend\Debug\Debug::dump($result);//die;
        return $result;
    }

    public function fetchRecentInfectionByDistrictChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));

        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "not_reported" => new Expression("(SUM(CASE WHEN (r.gender = 'not_reported') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END))"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END))"),
                        "not_reported" => new Expression("(SUM(CASE WHEN (r.gender = 'not_reported') THEN 1 ELSE 0 END))"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            //->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !='')")
            ->order('total DESC')
            ->group('d.district_name');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //echo($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            if ($sRow["district_name"] == null) {
                $sRow["district_name"] = 'No District';
            }

            $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
            $result['finalOutCome']['Male'][$j] = (isset($sRow['male']) && $sRow['male'] != null) ? round($sRow['male'], 2) : 0;
            $result['finalOutCome']['Female'][$j] = (isset($sRow['female']) && $sRow['female'] != null) ? round($sRow['female'], 2) : 0;
            $result['finalOutCome']['Gender Missing'][$j] = (isset($sRow['not_reported']) && $sRow['not_reported'] != null) ? round($sRow['not_reported'], 2) : 0;
            $result['district_name'][$j] = ($sRow["district_name"]) . " (N=$n)";

            $result['total'] += $n;

            $j++;
        }
        return $result;
    }

    public function fetchRecentInfectionByAgeChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'gender',
                        "total" => new Expression('COUNT(*)'),
                        "15T24" => new Expression("(SUM(CASE WHEN (age >= '15' AND r.age<='24') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "25T34" => new Expression("(SUM(CASE WHEN (age >= '25' AND r.age<='34') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "35T44" => new Expression("(SUM(CASE WHEN (age >= '35' AND r.age<='44') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "45+" => new Expression("(SUM(CASE WHEN (r.age>='45') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'gender',
                        "total" => new Expression('COUNT(*)'),
                        "15T24" => new Expression("(SUM(CASE WHEN (age >= '15' AND r.age<='24') THEN 1 ELSE 0 END))"),
                        "25T34" => new Expression("(SUM(CASE WHEN (age >= '25' AND r.age<='34') THEN 1 ELSE 0 END))"),
                        "35T44" => new Expression("(SUM(CASE WHEN (age >= '35' AND r.age<='44') THEN 1 ELSE 0 END))"),
                        "45+" => new Expression("(SUM(CASE WHEN (r.age>='45') THEN 1 ELSE 0 END))"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array())
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array(), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array())
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array())
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array(), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            //->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !='')")
            ->group('r.gender')
            ->order("gender ASC");

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format'] = $format;

        $result['ageGroup'][0] = '15-24';
        $result['ageGroup'][1] = '25-34';
        $result['ageGroup'][2] = '35-44';
        $result['ageGroup'][3] = '45+';

        foreach ($rResult as $sRow) {
            if ($sRow["gender"] == null) {
                continue;
            }

            if ($sRow["gender"] == 'not_reported') {
                $sRow["gender"] = 'Gender Missing';
            } else {
                $sRow["gender"] = ucwords($sRow["gender"]);
            }
            $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
            $result['finalOutCome'][$sRow["gender"]]['15-24'] += (isset($sRow['15T24']) && $sRow['15T24'] != null) ? round($sRow['15T24'], 2) : 0;
            $result['finalOutCome'][$sRow["gender"]]['25-34'] += (isset($sRow['25T34']) && $sRow['25T34'] != null) ? round($sRow['25T34'], 2) : 0;
            $result['finalOutCome'][$sRow["gender"]]['35-44'] += (isset($sRow['35T44']) && $sRow['35T44'] != null) ? round($sRow['35T44'], 2) : 0;
            $result['finalOutCome'][$sRow["gender"]]['45+'] += (isset($sRow['45+']) && $sRow['45+'] != null) ? round($sRow['45+'], 2) : 0;

            $result['total'] += $n;
            $j++;
        }
        //\Zend\Debug\Debug::dump($result);die;
        return $result;
    }

    public function fetchRecentViralLoadChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(
                array(
                    'gender',
                    "1000<10K" => new Expression("(SUM(CASE WHEN (r.vl_result >= 1000 AND r.vl_result<10000) THEN 1 ELSE 0 END))"),
                    "10K100K" => new Expression("(SUM(CASE WHEN (r.vl_result >= 10000 AND r.vl_result<100000) THEN 1 ELSE 0 END))"),
                    "100K1M" => new Expression("(SUM(CASE WHEN (r.vl_result >= 100000 AND r.vl_result<1000000) THEN 1 ELSE 0 END))"),
                    "1M>" => new Expression("(SUM(CASE WHEN (r.vl_result>=1000000) THEN 1 ELSE 0 END))"),
                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'))
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'))
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            ->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date not like'')")
            ->group('r.vl_result');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        /*
        $result['rnaGroup'][0] = '1000<10K';
        $result['rnaGroup'][1] = '10K100K';
        $result['rnaGroup'][2] = '100K1M';
        $result['rnaGroup'][3] = '1M>';
         */

        foreach ($rResult as $sRow) {
            $result['finalOutCome']['1000<10K'] += (isset($sRow['1000<10K']) && $sRow['1000<10K'] != null) ? $sRow['1000<10K'] : 0;
            $result['finalOutCome']['10K100K'] += (isset($sRow['10K100K']) && $sRow['10K100K'] != null) ? $sRow['10K100K'] : 0;
            $result['finalOutCome']['100K1M'] += (isset($sRow['100K1M']) && $sRow['100K1M'] != null) ? $sRow['100K1M'] : 0;
            $result['finalOutCome']['1M>'] += (isset($sRow['1M>']) && $sRow['1M>'] != null) ? $sRow['1M>'] : 0;

            $result['total'] += $sRow['1000<10K'] + $sRow['10K100K'] + $sRow['100K1M'] + $sRow['1M>'];
            $j++;
        }

        return $result;
    }

    public function fetchDistrictWiseRecencyResult($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $general = new CommonService();
        $aColumns = array('d.district_name');
        $orderColumns = array('d.district_name', 'totalSamples', 'samplesReceived', 'samplesRejected', 'samplesTestBacklog', 'samplesTestVlPending', 'samplesTestedRecency', 'samplesTestedViralLoad', 'samplesFinalOutcome', 'printedCount', 'samplesFinalLongTerm', '', 'ritaRecent', '', 'samplesFinalInconclusive', '', 'samplesInvalid');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))
            ->columns(
                array(
                    "totalSamples" => new Expression('COUNT(*)'),
                    "samplesReceived" => new Expression("SUM(CASE
                                                               WHEN (((r.sample_receipt_date is NOT NULL) )) THEN 1
                                                               ELSE 0
                                                               END)"),
                    "samplesRejected" => new Expression("SUM(CASE
                                                                 WHEN (((r.recency_test_not_performed ='sample_rejected') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestedRecency" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome='Assay Recent') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestedViralLoad" => new Expression("SUM(CASE
                                                                 WHEN (( r.term_outcome='Assay Recent' AND (r.vl_result is NOT NULL or r.vl_result != '') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesFinalOutcome" => new Expression("SUM(CASE
                                                                 WHEN (((r.final_outcome is NOT NULL and r.final_outcome != '') )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestBacklog" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome is null AND (recency_test_not_performed IS NULL OR recency_test_not_performed ='') ) )) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesTestVlPending" => new Expression("SUM(CASE
                                                                 WHEN (((r.term_outcome='Assay Recent' AND (vl_result='' or vl_result is null)))) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesFinalLongTerm" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='Long Term')) THEN 1
                                                                 ELSE 0
                                                                 END)"),

                    "samplesFinalInconclusive" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome like 'Inconclusive')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "samplesInvalid" => new Expression("SUM(CASE
                                                                 WHEN ((r.control_line like 'absent')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "ritaRecent" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='RITA Recent')) THEN 1
                                                                 ELSE 0
                                                                 END)"),
                    "printedCount" => new Expression("SUM(CASE
                                                                 WHEN ((r.result_printed_on not like '' and r.result_printed_on is not null)) THEN 1
                                                                 ELSE 0
                                                                 END)"),

                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            //->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = f.province', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = f.district', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.location_two');

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if (isset($sOrder) && $sOrder != "") {
            if (($sOrder == "d.district_name asc") || ($sOrder == "d.district_name desc")) {
                $sQuery->order(new Expression("CASE WHEN `district_name` IS NULL OR `district_name` = '' THEN 1 ELSE 0 END, " . $sOrder));
            } else {
                $sQuery->order($sOrder);
            }
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $queryContainer->exportDistrictwiseRecencyResult = $sQuery;

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //echo $sQueryStr;die;

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            //->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.location_two');
        if ($this->sessionLogin->facilityMap != null) {
            $iQuery = $iQuery->where('r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . ')');
        }
        $iQueryStr = $sql->buildSqlString($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
            "footerData" => array()
        );

        foreach ($rResult as $aRow) {
            $ltPercentage = $invalidPercentage = $inconlusivePercentage = $recentPercentage = "0 %";
            if (trim($aRow['samplesFinalLongTerm']) != "") {
                $ltPercentage = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (isset($aRow['ritaRecent']) && !empty($aRow['ritaRecent'])) {
                $recentPercentage = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (isset($aRow['samplesFinalInconclusive']) && !empty($aRow['samplesFinalInconclusive'])) {
                $inconlusivePercentage = round((($aRow['samplesFinalInconclusive'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (isset($aRow['samplesInvalid']) && !empty($aRow['samplesInvalid'])) {
                $invalidPercentage = round((($aRow['samplesInvalid'] / $aRow['samplesTestedRecency']) * 100), 2) . '%';
            }
            $row = array();

            $row[] = $aRow['district_name'];
            $row[] = $aRow['totalSamples'];
            $row[] = $aRow['samplesReceived'];
            $row[] = $aRow['samplesRejected'];
            $row[] = $aRow['samplesTestBacklog'];
            $row[] = $aRow['samplesTestVlPending'];
            $row[] = $aRow['samplesTestedRecency'];
            $row[] = $aRow['samplesTestedViralLoad'];
            $row[] = $aRow['samplesFinalOutcome'];
            $row[] = $aRow['printedCount'];
            $row[] = $aRow['samplesFinalLongTerm'];
            $row[] = $ltPercentage;
            $row[] = $aRow['ritaRecent'];
            $row[] = $recentPercentage;
            $row[] = $aRow['samplesFinalInconclusive'];
            $row[] = $inconlusivePercentage;
            $row[] = $aRow['samplesInvalid'];
            $row[] = $invalidPercentage;
            $output[] = $row;

            $output['aaData'][] = $row;
        }



        foreach ($aResultFilterTotal as $aRow) {

            $row = array();

            $output['footerData'][0] = 'Overall Total';
            $output['footerData'][1] += $aRow['totalSamples'];
            $output['footerData'][2] += $aRow['samplesReceived'];
            $output['footerData'][3] += $aRow['samplesRejected'];
            $output['footerData'][4] += $aRow['samplesTestBacklog'];
            $output['footerData'][5] += $aRow['samplesTestVlPending'];
            $output['footerData'][6] += $aRow['samplesTestedRecency'];
            $output['footerData'][7] += $aRow['samplesTestedViralLoad'];
            $output['footerData'][8] += $aRow['samplesFinalOutcome'];
            $output['footerData'][9] += $aRow['printedCount'];
            $output['footerData'][10] += $aRow['samplesFinalLongTerm'];
            $output['footerData'][11] = "";
            $output['footerData'][12] += $aRow['ritaRecent'];
            $output['footerData'][13] = "";
            $output['footerData'][14] += $aRow['samplesFinalInconclusive'];
            $output['footerData'][15] = "";
            $output['footerData'][16] += $aRow['samplesInvalid'];
            $output['footerData'][17] = "";
        }


        if ($output['footerData'][8] > 0) {
            $output['footerData'][11] = round(($output['footerData'][10] / $output['footerData'][8]) * 100, 2) . "%";
        }

        if ($output['footerData'][8] > 0) {
            $output['footerData'][13] = round(($output['footerData'][12] / $output['footerData'][8]) * 100, 2) . "%";
        }

        if ($output['footerData'][8] > 0) {
            $output['footerData'][15] = round(($output['footerData'][14] / $output['footerData'][8]) * 100, 2) . "%";
        }

        if (($output['footerData'][1] - $output['footerData'][4]) > 0) {
            $output['footerData'][17] = round(($output['footerData'][16] / ($output['footerData'][1] - $output['footerData'][4])) * 100, 2) . "%";
        }
        return $output;
    }

    public function fetchModalityWiseFinalOutcomeChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'testing_facility_type',
                        "totalSamples" => new Expression('COUNT(*)'),
                        "assayRecent" => new Expression("(SUM(CASE
                                             WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                             ELSE 0
                                             END) / COUNT(*)) * 100"),
                        "assayLongTerm" => new Expression("(SUM(CASE
                                             WHEN ((term_outcome ='Long Term' OR term_outcome ='long term')) THEN 1
                                             ELSE 0
                                             END) / COUNT(*)) * 100"),
                        "assayInvalid" => new Expression("(SUM(CASE
                                             WHEN ((term_outcome ='Invalid' OR term_outcome ='invalid')) THEN 1
                                             ELSE 0
                                             END)/ COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'testing_facility_type',
                        "totalSamples" => new Expression('COUNT(*)'),
                        "assayRecent" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                             ELSE 0
                                             END)"),
                        "assayLongTerm" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Long Term' OR term_outcome ='long term')) THEN 1
                                             ELSE 0
                                             END)"),
                        "assayInvalid" => new Expression("SUM(CASE
                                             WHEN ((term_outcome ='Invalid' OR term_outcome ='invalid')) THEN 1
                                             ELSE 0
                                             END)"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('tft' => 'testing_facility_type'), 'tft.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('testing_facility_type');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            if ($sRow["testing_facility_type_name"] == null) {
                continue;
            }

            $n = (isset($sRow['totalSamples']) && $sRow['totalSamples'] != null) ? $sRow['totalSamples'] : 0;

            //$result['finalOutCome']['Total'][$j] = (isset($sRow['totalSamples']) && $sRow['totalSamples'] != null) ? $sRow['totalSamples'] : 0;
            $result['finalOutCome']['Assay Long Term'][$j] = (isset($sRow['assayLongTerm']) && $sRow['assayLongTerm'] != null) ? round($sRow['assayLongTerm'], 2) : 0;
            $result['finalOutCome']['Assay Recent'][$j] = (isset($sRow['assayRecent']) && $sRow['assayRecent'] != null) ? round($sRow['assayRecent'], 2) : 0;
            $result['finalOutCome']['Assay Invaild'][$j] = (isset($sRow['assayInvalid']) && $sRow['assayInvalid'] != null) ? round($sRow['assayInvalid'], 2) : 0;
            $result['modality'][$j] = ($sRow["testing_facility_type_name"]) . " (N=$n)";

            $result['total'] += $n;

            $j++;
        }
        //\Zend\Debug\Debug::dump($result); die;
        return $result;
    }

    public function fetchRecentInfectionBySexLineChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();

        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';

        $sQuery = $sql->select()->from(array('r' => 'recency'));

        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'added_on',
                        "total" => new Expression('COUNT(*)'),
                        "monthyear" => new Expression("DATE_FORMAT(r.added_on, '%b-%Y')"),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "not_reported" => new Expression("(SUM(CASE WHEN ((r.gender = 'not_reported' or r.gender='')) THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'added_on',
                        "total" => new Expression('COUNT(*)'),
                        "monthyear" => new Expression("DATE_FORMAT(r.added_on, '%b-%Y')"),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END))"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END))"),
                        "not_reported" => new Expression("(SUM(CASE WHEN ((r.gender = 'not_reported' or r.gender='')) THEN 1 ELSE 0 END))"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            ->group(array(new Expression("DATE_FORMAT(added_on,'%b-%Y')")))
            ->order("r.added_on");

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        $j = 0;
        $result = array();
        $result['format'] = $format;

        foreach ($rResult as $sRow) {
            if ($sRow["monthyear"] == null) {
                continue;
            }

            $result['finalOutComeTotal'][$j] = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
            $result['finalOutComeMale'][$j] = (isset($sRow["male"]) && $sRow["male"] != null) ? round($sRow["male"], 2) : 0;
            $result['finalOutComeFemale'][$j] = (isset($sRow["female"]) && $sRow["female"] != null) ? round($sRow["female"], 2) : 0;
            $result['finalOutComeGenderMissing'][$j] = (isset($sRow['not_reported']) && $sRow['not_reported'] != null) ? round($sRow['not_reported'], 2) : 0;

            $result['date'][$j] = $sRow["monthyear"];
            $j++;
        }

        return $result;
    }

    public function fetchDistrictWiseMissingViralLoadChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "missingViralLoad" => new Expression("(SUM(CASE
                                WHEN (((r.term_outcome='Assay Recent' AND (vl_result is null or vl_result='')) )) THEN 1 ELSE 0 END)/COUNT(*))*100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "missingViralLoad" => new Expression("SUM(CASE
                                WHEN (((r.term_outcome='Assay Recent' AND (vl_result is null or vl_result='')) )) THEN 1 ELSE 0 END)"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('district_name');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            if ($sRow["district_name"] == null || $sRow['missingViralLoad'] == 0) {
                continue;
            }

            $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
            $result['finalOutCome']['Missing Viral Load'][$j] = (isset($sRow['missingViralLoad']) && $sRow['missingViralLoad'] != null) ? round($sRow['missingViralLoad'], 2) : 0;
            $result['districtName'][$j] = ($sRow["district_name"]) . " (N=$n)";
            $result['total'] += $n;

            $j++;
        }

        return $result;
    }

    public function fetchModalityWiseMissingViralLoadChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "missingViralLoad" => new Expression("(SUM(CASE
                                WHEN (((r.term_outcome='Assay Recent' AND (vl_result is null or vl_result='')) )) THEN 1 ELSE 0 END)/COUNT(*))*100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        "total" => new Expression('COUNT(*)'),
                        "missingViralLoad" => new Expression("SUM(CASE
                                WHEN (((r.term_outcome='Assay Recent' AND (vl_result is null or vl_result='')) )) THEN 1 ELSE 0 END)"),
                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('tft' => 'testing_facility_type'), 'tft.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name'), 'left')
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.testing_facility_type');

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $parameters['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }
        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format'] = $format;
        foreach ($rResult as $sRow) {
            if ($sRow['missingViralLoad'] == 0) {
                continue;
            }

            $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;
            $result['finalOutCome']['Missing Viral Load'][$j] = (isset($sRow['missingViralLoad']) && $sRow['missingViralLoad'] != null) ? round($sRow['missingViralLoad'], 2) : 0;

            if ($sRow["testing_facility_type_name"] == null && $sRow['missingViralLoad'] > 0) {
                $result['modality'][$j] = "Missing Point-of-Testing" . " (N=$n)";
            } else {
                $result['modality'][$j] = ($sRow["testing_facility_type_name"]) . " (N=$n)";
            }

            $result['total'] += $n;

            $j++;
        }
        return $result;
    }

    public function fetchRecentInfectionByMonthSexChart($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
        $format = isset($parameters['format']) ? $parameters['format'] : 'percentage';
        $sQuery = $sql->select()->from(array('r' => 'recency'));
        if ($format == 'percentage') {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'added_on',
                        "total" => new Expression('COUNT(*)'),
                        "monthyear" => new Expression("DATE_FORMAT(r.added_on, '%b-%Y')"),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "not_reported" => new Expression("(SUM(CASE WHEN ((r.gender = 'not_reported' or r.gender='')) THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'added_on',
                        "total" => new Expression('COUNT(*)'),
                        "monthyear" => new Expression("DATE_FORMAT(r.added_on, '%b-%Y')"),
                        "male" => new Expression("(SUM(CASE WHEN (r.gender = 'male') THEN 1 ELSE 0 END))"),
                        "female" => new Expression("(SUM(CASE WHEN (r.gender = 'female') THEN 1 ELSE 0 END))"),
                        "not_reported" => new Expression("(SUM(CASE WHEN ((r.gender = 'not_reported' or r.gender='')) THEN 1 ELSE 0 END))"),

                    )
                );
        }

        $sQuery = $sQuery
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array())
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array(), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array())
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array())
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array(), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            ->group(array(new Expression("DATE_FORMAT(r.added_on,'%b-%Y')")))
            ->order("r.added_on");

        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($parameters['fName'])));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('p.province_id' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('d.district_id' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '' && $parameters['locationThree'] != 'other') {
                $sQuery = $sQuery->where(array('c.city_id' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['sampleTestedDates']) && trim($parameters['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }

        if ($this->sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('(r.facility_id IN (' . $this->sessionLogin->facilityMap . ') OR r.testing_facility_id IN (' . $this->sessionLogin->facilityMap . '))');
        }

        $sQueryStr = $sql->buildSqlString($sQuery);

        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $j = 0;
        $result = array();
        $result['format'] = $format;

        foreach ($rResult as $sRow) {
            if ($sRow["monthyear"] == null) {
                continue;
            }

            $n = (isset($sRow['total']) && $sRow['total'] != null) ? $sRow['total'] : 0;

            $result['finalOutCome']['Female'][$j] = (isset($sRow['female']) && $sRow['female'] != null) ? round($sRow['female'], 2) : 0;
            $result['finalOutCome']['Male'][$j] = (isset($sRow['male']) && $sRow['male'] != null) ? round($sRow['male'], 2) : 0;
            $result['finalOutCome']['Gender Missing'][$j] = (isset($sRow['not_reported']) && $sRow['not_reported'] != null) ? round($sRow['not_reported'], 2) : 0;
            $result['monthyear'][$j] = ($sRow["monthyear"]) . " (N=$n)";

            $result['total'] += $n;

            $j++;
        }
        return $result;
    }


    public function fetchRecentDetailsForPDF($recencyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName' => 'facility_name'))
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('recency_id' => $recencyId));

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function fetchLTermDetailsForPDF($recencyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testFacilityName' => 'facility_name'))
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('recency_id' => $recencyId));

        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }



    public function UpdatePdfUpdatedDateDetails($recenyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->where(array('(recency_id="' . $recenyId . '" )'));
        $fQueryStr = $sql->buildSqlString($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if (!isset($fResult['result_printed_on']) && $fResult['result_printed_on'] == '') {
            $results =  $this->update(array('result_printed_on' => date("Y-m-d H:i:s")), array('recency_id' => $recenyId));
        } else {
            $results = "0";
        }
        return $recenyId;
    }


    public function UpdateMultiplePdfUpdatedDateDetails($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->where("recency_id IN(" . $params['selectedSampleId'] . ")");
        $fQueryStr = $sql->buildSqlString($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        foreach ($fResult as $res) {
            if (!isset($res['result_printed_on']) && $res['result_printed_on'] == '') {
                $results =  $this->update(array('result_printed_on' => date("Y-m-d H:i:s")), array('recency_id' => $res['recency_id']));
            } else {
                $results = "0";
            }
        }
        return $results;
    }

    public function saveVlTestResultApi($params)
    {
        $common = new CommonService();
        $responseStatus['status'] = 'fail';
        if (isset($params['sampleId']) && $params['sampleId'] != "") {
            $data = array(
                'lis_vl_result'           => $params['result'],
                'lis_vl_test_date'        => date('Y-m-d', strtotime($params['sampleTestedDatetime'])),
                'lis_vl_result_entry_date'  => $common->getDateTime()
                // 'vl_result'           => $params['result'],
                // 'vl_test_date'        => date('Y-m-d', strtotime($params['sampleTestedDatetime'])),
                // 'vl_result_entry_date'  => $common->getDateTime() 
            );
            $results =  $this->update($data, array('sample_id' => $params['sampleId']));
        }
        if (isset($results) && $results > 0) {
            $responseStatus['status'] = 'success';
        }
        return $responseStatus;
    }

    public function fetchReqVlTestOnVlsmDetails($params)
    {
        $common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('recency_id', 'sample_id'))
            ->where("vl_result IS NULL")
            ->where(array('vl_request_sent' => 'no'));
        $start_date = $end_date = date('Y-m-d');
        if (isset($params['sampleTestedDates']) && trim($params['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($params['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if ($params['facilityId'] != '') {
            $sQuery->where(array('r.facility_id' => base64_decode($params['facilityId'])));
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        // die($sQueryStr);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getDataBySampleId($sId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array(
            'recency_id', 'facility_id', 'sample_id', 'patient_id', 'sample_collection_date', 'vl_result', 'received_specimen_type', 'dob', 'age', 'gender'
        ))->where(array('sample_id' => $sId));
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function saveRequestFlag($rId)
    {
        $common = new CommonService();
        $this->update(array(
            'vl_request_sent'         => 'yes',
            'vl_request_sent_date_time' => $common->getDateTime()
        ), array('recency_id' => $rId));
    }

    public function fetchKitInfo($kitNo)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from('test_kit_information')->columns(array('test_id', 'reference_result', 'kit_lot_no', 'kit_expiry_date' => new Expression("DATE_FORMAT(kit_expiry_date,'%d-%b-%Y')"), 'status', 'added_on'))->where(array('status' => 'active'));
        if (isset($kitNo) && $kitNo != "") {
            $sQuery = $sQuery->where(array('kit_lot_no' => $kitNo));
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function fetchModalityDetails($params)
    {
        $common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $data = array();
        $sQuery = $sql->select()->from(array('r' => $this->table))->columns(array(
            'rtriRecent15-19M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent15-19F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent20-24M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent20-24F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent25-29M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent25-29F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent30-34M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent30-34F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent35-39M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent35-39F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent40-44M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent40-44F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent45-49M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent45-49F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "female" AND term_outcome IS NOT NULL AND term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent50+M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriRecent50+F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'rtriLT15-19M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT15-19F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT20-24M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT20-24F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT25-29M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT25-29F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT30-34M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT30-34F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT35-39M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT35-39F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT40-44M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT40-44F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT45-49M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT45-49F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT50+M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "male" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'rtriLT50+F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "female" AND r.term_outcome IS NOT NULL AND r.term_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent15-19M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent15-19F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent20-24M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent20-24F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent25-29M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent25-29F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent30-34M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent30-34F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent35-39M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent35-39F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent40-44M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent40-44F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent45-49M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent45-49F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent50+M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedRecent50+F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Recent%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT15-19M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT15-19F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="15" AND r.age <= "19" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT20-24M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT20-24F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="20" AND r.age <= "24" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT25-29M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT25-29F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="25" AND r.age <= "29" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT30-34M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT30-34F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="30" AND r.age <= "34" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT35-39M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT35-39F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="35" AND r.age <= "39" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT40-44M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT40-44F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="40" AND r.age <= "45" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT45-49M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT45-49F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="45" AND r.age <= "49" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT50+M' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "male" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
            'confirmedLT50+F' => new Expression('SUM(CASE WHEN ( r.age IS NOT NULL AND r.age >="50" AND r.gender = "female" AND r.final_outcome IS NOT NULL AND r.final_outcome LIKE "%Long Term%" ) THEN 1 ELSE 0 END)'),
        ));
        $sessionLogin = new Container('credo');
        $roleCode = $sessionLogin->roleCode;
        if (isset($params['fName']) && $params['fName'] != '') {
            if ($roleCode != 'admin') {
                $sQuery->where(array('r.facility_id' => base64_decode($params['fName'])));
            } else {
                $sQuery = $sQuery->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'));
                $sQuery->where(array('r.facility_id' => base64_decode($params['fName'])));
            }
        }
        if ($roleCode != 'admin') {
            $sQuery = $sQuery->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'));
            $sQuery = $sQuery->join(array('ufm' => 'user_facility_map'), 'f.facility_id=ufm.facility_id', array());
            $sQuery = $sQuery->join(array('u' => 'users'), 'ufm.user_id=u.user_id', array());
            $sQuery = $sQuery->where(array('u.user_id' => $sessionLogin->userId));
        }
        if (isset($params['testingFacility']) && $params['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $params['testingFacility']));
        }
        if (isset($params['locationOne']) && $params['locationOne'] != '') {
            $sQuery = $sQuery->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'));
            $sQuery = $sQuery->where(array('p.province_id' => $params['locationOne']));

            if (isset($params['locationTwo']) && $params['locationTwo'] != '') {
                $sQuery = $sQuery->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'));
                $sQuery = $sQuery->where(array('d.district_id' => $params['locationTwo']));
            }
            if (isset($params['locationThree']) && $params['locationThree'] != '') {
                $sQuery = $sQuery->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'));
                $sQuery = $sQuery->where(array('c.city_id' => $params['locationThree']));
            }
        }
        if (isset($params['hivRecencyTest']) && trim($params['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($params['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if (isset($params['sampleTestedDates']) && trim($params['sampleTestedDates']) != '') {
            $s_c_date = explode("to", $_POST['sampleTestedDates']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $common->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $common->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if (isset($params['sampleTestedDates']) && $params['sampleTestedDates'] != '') {
            $sQuery = $sQuery->where(array("r.sample_collection_date >='" . $start_date . "'", "r.sample_collection_date <='" . $end_date . "'"));
        }
        if (isset($params['testingModality']) && $params['testingModality'] != '') {
            $sQuery = $sQuery->where(array("r.testing_facility_type" => $params['testingModality']));
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }


    public function cryptoJsAesDecrypt($passphrase, $jsonString)
    {
        $jsondata = json_decode($jsonString, true);
        try {
            $salt = hex2bin($jsondata["s"]);
            $iv  = hex2bin($jsondata["iv"]);
        } catch (Exception $e) {
            return null;
        }
        $ct = base64_decode($jsondata["ct"]);
        $concatedPassphrase = $passphrase . $salt;
        $md5 = array();
        $md5[0] = md5($concatedPassphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1] . $concatedPassphrase, true);
            $result .= $md5[$i];
        }
        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        return json_decode($data, true);
    }

    public function cryptoJsAesEncrypt($passphrase, $value)
    {
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32, 16);
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return json_encode($data);
    }

    public function fetchPrintResultsDetails($parameters)
    {
        $sessionLogin = new Container('credo');
        $aColumns = array('r.sample_id', 'DATE_FORMAT(r.sample_collection_date,"%d-%b-%Y")', 'f.facility_name', 'r.patient_id', 'r.gender', 'r.age', 'ft.facility_name', 'tft.testing_facility_type_name', 'r.final_outcome');
        $orderColumns = array('r.sample_id', 'r.sample_collection_date', 'f.facility_name', 'r.patient_id', 'r.gender', 'r.age', 'ft.facility_name', 'tft.testing_facility_type_name', 'r.final_outcome');

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
                    $sOrder .= $orderColumns[intval($parameters['iSortCol_' . $i])] . " " . ($parameters['sSortDir_' . $i]) . ",";
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
        $general = new CommonService();
        $sQuery = $sql->select()->quantifier(new Expression('SQL_CALC_FOUND_ROWS'))->from(array('r' => 'recency'))->columns(array('result_printed_on', 'recency_id', 'age', 'gender', 'sample_id', 'patient_id', 'final_outcome', 'sample_collection_date', 'testing_facility_type'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('tft' => 'testing_facility_type'), 'tft.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name'), 'left');

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($sessionLogin->facilityMap != null) {
            $sQuery = $sQuery->where('r.facility_id IN (' . $sessionLogin->facilityMap . ')');
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['viewFlag'] == 'printed') {
            $sQuery->where('r.result_printed_on IS NOT NULL');
        } else if ($parameters['viewFlag'] == 'not-printed') {
            $sQuery->where('r.result_printed_on IS NULL');
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('r.province' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('r.district' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('r.city' => $parameters['locationThree']));
            }
        }
        if (isset($parameters['hivRecencyTest']) && trim($parameters['hivRecencyTest']) != '') {
            $s_c_date = explode("to", $_POST['hivRecencyTest']);
            if (isset($s_c_date[0]) && trim($s_c_date[0]) != "") {
                $start_date = $general->dbDateFormat(trim($s_c_date[0]));
            }
            if (isset($s_c_date[1]) && trim($s_c_date[1]) != "") {
                $end_date = $general->dbDateFormat(trim($s_c_date[1]));
            }
        }

        if ($parameters['hivRecencyTest'] != '') {
            $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . $start_date . "'", "r.hiv_recency_test_date <='" . $end_date . "'"));
        }
        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        $sQueryStr = $sql->buildSqlString($sQuery);
        // die($sQueryStr);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        $aResultFilterTotal = $dbAdapter->query("SELECT FOUND_ROWS() as `totalCount`", $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $row[] = $aRow['sample_id'];
            $row[] = $general->humanDateFormat($aRow['sample_collection_date']);
            $row[] = ucwords($aRow['facility_name']);
            $row[] = $aRow['patient_id'];
            $row[] = ucwords($aRow['gender']);
            $row[] = $aRow['age'];
            $row[] = ucwords($aRow['testing_facility_name']);
            $row[] = ucwords($aRow['testing_facility_type_name']);
            $row[] = $aRow['final_outcome'];
            if ($aRow['final_outcome'] != "") {
                $row[] = '<a class="btn btn-primary" href="javascript:void(0)" onclick="generatePdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>';
            } else {
                $row[] = '';
            }
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchSampleId()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $date = date("y");
        $sQuery = $sql->select()->from('recency')
            ->columns(array(
                "sample_prefix_id" => new Expression("MAX(sample_prefix_id)"), "sample_id_year_prefix", "sample_id_string_prefix"
            ))
            ->where(array('sample_id_year_prefix' => $date));;
        $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        $sampleIdYearPrefix = $rResult['sample_id_year_prefix'];
        $sampleIdStringPrefix = $rResult['sample_id_string_prefix'];
        $samplePrefixId = $rResult['sample_prefix_id'];
        if (isset($samplePrefixId) && trim($samplePrefixId) != "") {
            $samplePrefixId = (int) $samplePrefixId + 1;
            $samplePrefixId = str_pad($samplePrefixId, 6, "0", STR_PAD_LEFT);
            $recencySampleId['sample_prefix_id'] = $samplePrefixId;
            $recencySampleId['sample_id_year_prefix'] = $date;
            $recencySampleId['sample_id_string_prefix'] = "RT";
            $recencySampleId['recencyId'] = "RT" . $date . "" . $samplePrefixId;
        } else {
            $samplePrefixId = "000001";
            $recencySampleId['sample_prefix_id'] = $samplePrefixId;
            $recencySampleId['sample_id_year_prefix'] = $date;
            $recencySampleId['sample_id_string_prefix'] = "RT";
            $recencySampleId['recencyId'] = "RT" . $date . "" . $samplePrefixId;
        }
        return $recencySampleId;
    }
    public function fetchRecencyDateBasedTestKit($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fResult = '';
        $common = new CommonService();
        if (isset($params['recencyTestDate']) && $params['recencyTestDate'] != null) {
            $recencyTestDate = $common->dbDateFormat($params['recencyTestDate']);
            $sQuery = $sql->select()->from(array('t' => 'test_kit_information'))
                ->where("kit_expiry_date <= '" . $recencyTestDate . "'")
                ->where(array('status' => 'active'));
            $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
            return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        }
    }

    public function checkPatientIdValidation($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $results = '';
        $value = trim($params['value']);
        $editPatientId = $params['editPatientId'];
        try {
            $sql = new Sql($dbAdapter);
            if ($editPatientId == '' || $editPatientId == 'null') {
                $sQuery = $sql->select()->from('recency')
                    ->where(array('patient_id' => $value))
                    ->order('sample_collection_date' . ' DESC')
                    ->limit(1);
                $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
                $results = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            } else {
                $sQuery = $sql->select()->from('recency')
                    ->where(array("patient_id ='" . $value . "'", "patient_id !='" . $editPatientId . "'"))
                    ->order("sample_collection_date DESC")
                    ->limit(1);
                $sQueryStr = $sql->buildSqlString($sQuery); // Get the string of the Sql, instead of the Select-instance
                $results =  $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            }
            return $results;
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    //refer getVlRequestSentOnYes Function
    public function getVlRequestSentOnYes()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->where(array('vl_request_sent="yes"'))
            ->limit('10');

        $fQueryStr = $sql->buildSqlString($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $fResult;
    }

    //refer updateVlRequestSentNO Function
    public function updateVlRequestSentNO($rId,$vlSampleCode)
    {
        $common = new CommonService();
        $this->update(array(
            'vl_request_sent'           => 'no',
            'vl_request_sent_date_time' => $common->getDateTime(),
            'lis_vl_sample_code'        => $vlSampleCode
        ), array('recency_id' => $rId));
    }

    //refer updatefinalOutComeBySampleId Function
    public function updatefinalOutComeBySampleId($data,$finaloutcome)
    {
        $common = new CommonService();
        $this->update(array(
            'vl_lab_id'                => $data['labId'],
            'vl_result'                => $data['result'],
            'vl_test_date'             => $data['sampleTestingDateAtLab'],
            'vl_result_entry_date'     => date("Y-m-d H:i:s"),
            'final_outcome'            => $finaloutcome,
            'final_outcome_updated_on' => $common->getDateTime()
        ), array('sample_id' => $data['serialNo']));
    }

    //refer fetchPendingVlSampleData Function
    public function fetchPendingVlSampleData()
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('unique_id','facility_id','sample_collection_date','lis_vl_sample_code'))
                ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
                ->where(array('r.term_outcome' => 'Assay Recent'))
                ->where(array('r.lis_vl_sample_code IS NOT NULL AND r.lis_vl_sample_code NOT like ""'))
                ->where(array('r.vl_result is null OR r.vl_result=""'));
        $sQueryStr = $sql->buildSqlString($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }
}
