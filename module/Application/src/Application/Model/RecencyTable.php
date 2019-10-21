<?php

namespace Application\Model;

use Application\Service\CommonService;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;
use \Application\Model\CityTable;
use \Application\Model\DistrictTable;
use \Application\Model\FacilitiesTable;

class RecencyTable extends AbstractTableGateway
{

    protected $table = 'recency';
    public $vlResultOptionArray = array('target not detected', 'below detection line', 'tnd', 'bdl', 'failed', '&lt; 20', '&lt; 40', '< 20', '< 40', '< 400', '< 800', '<20', '<40');
    public $vlFailOptionArray = array('fail', 'failed');

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
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

    public function fetchRecencyDetails($parameters)
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left')
            ->join(array('tf' => 'testing_facility_type'), 'tf.testing_facility_type_id = r.testing_facility_type', array('testing_facility_type_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('rp' => 'risk_populations'), 'rp.rp_id = r.risk_population', array('name'), 'left');
        //->order("r.recency_id DESC");
        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
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
            if ($parameters['vlResult'] == 'panding') {
                $sQuery->where(array('term_outcome' => 'Assay Recent'));
            } else if ($parameters['vlResult'] == 'vl_load_tested') {
                $sQuery->where('term_outcome = "" OR  term_outcome = NULL ');
            }
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        if ($roleCode == 'user') {
            $sQuery = $sQuery->where('r.added_by=' . $sessionLogin->userId);
        }

        $queryContainer->exportRecencyDataQuery = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('facility_name'), 'left')
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'), 'left');

        if ($roleCode == 'user') {
            $iQuery = $iQuery->where('r.added_by=' . $sessionLogin->userId);
        }

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );
        $actionBtn = "";
        foreach ($rResult as $aRow) {

            $row = array();
            $formInitiationDate = '';
            if ($aRow['form_initiation_datetime'] != '' && $aRow['form_initiation_datetime'] != '0000-00-00 00:00:00' && $aRow['form_initiation_datetime'] != null) {
                $formInitiationAry = explode(" ", $aRow['form_initiation_datetime']);
                $formInitiationDate = $common->humanDateFormat($formInitiationAry[0]) . " " . $formInitiationAry[1];
            }
            $formTransferDate = '';
            if ($aRow['form_transfer_datetime'] != '' && $aRow['form_transfer_datetime'] != '0000-00-00 00:00:00' && $aRow['form_transfer_datetime'] != null) {
                $formTransferAry = explode(" ", $aRow['form_transfer_datetime']);
                $formTransferDate = $common->humanDateFormat($formTransferAry[0]) . " " . $formTransferAry[1];
            }
            $row[] = $aRow['sample_id'];
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
            $row[] = str_replace("_", " ", ucwords($aRow['received_specimen_type']));

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

            $actionBtn = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">';
            if ($roleCode != 'manager') {
                $actionBtn .= '<a class="btn btn-danger" href="/recency/edit/' . base64_encode($aRow['recency_id']) . '"><i class="si si-pencil"></i> Edit</a>';
            }

            $actionBtn .= '<a class="btn btn-primary" href="/recency/view/' . base64_encode($aRow['recency_id']) . '"><i class="si si-eye"></i> View</a>
                         <a class="btn btn-primary" href="javascript:void(0)" onclick="generatePdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>
                         </div>';
            $row[] = $actionBtn;

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
        // \Zend\Debug\Debug::dump($params);die;
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

            $data = array(
                'sample_id' => $params['sampleId'],
                'patient_id' => $params['patientId'],
                'facility_id' => base64_decode($params['facilityId']),
                'testing_facility_id' => ($params['testingFacilityId'] != '') ? base64_decode($params['testingFacilityId']) : null,
                'dob' => ($params['dob'] != '') ? $common->dbDateFormat($params['dob']) : null,
                'hiv_diagnosis_date' => ($params['hivDiagnosisDate'] != '') ? $common->dbDateFormat($params['hivDiagnosisDate']) : null,
                'hiv_recency_test_date' => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate'] != '') ? $common->dbDateFormat($params['hivRecencyTestDate']) : null,
                'recency_test_performed' => $params['recencyTestPerformed'],
                'recency_test_not_performed' => ($params['recencyTestPerformed'] == 'true') ? $params['recencyTestNotPerformed'] : null,
                'other_recency_test_not_performed' => ($params['recencyTestNotPerformed'] == 'other') ? $params['otherRecencyTestNotPerformed'] : null,
                'control_line' => (isset($params['controlLine']) && $params['controlLine'] != '') ? $params['controlLine'] : null,
                'positive_verification_line' => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine'] != '') ? $params['positiveVerificationLine'] : null,
                'long_term_verification_line' => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine'] != '') ? $params['longTermVerificationLine'] : null,
                'term_outcome' => $params['outcomeData'],
                'final_outcome' => $params['vlfinaloutcomeResult'],
                'age_not_reported' => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : no,
                'gender' => $params['gender'],
                'age' => ($params['age'] != '') ? $params['age'] : null,
                'marital_status' => $params['maritalStatus'],
                'residence' => $params['residence'],
                'education_level' => $params['educationLevel'],
                'risk_population' => base64_decode($params['riskPopulation']),
                'pregnancy_status' => $params['pregnancyStatus'],
                'current_sexual_partner' => $params['currentSexualPartner'],
                'past_hiv_testing' => $params['pastHivTesting'],
                'last_hiv_status' => $params['lastHivStatus'],
                'patient_on_art' => $params['patientOnArt'],
                'test_last_12_month' => $params['testLast12Month'],
                'location_one' => $params['location_one'],
                'location_two' => $params['location_two'],
                'location_three' => $params['location_three'],
                'exp_violence_last_12_month' => $params['expViolence'],
                'notes' => $params['comments'],
                'added_on' => date("Y-m-d H:i:s"),
                'added_by' => $logincontainer->userId,
                'form_initiation_datetime' => date("Y-m-d H:i:s"),
                'form_transfer_datetime' => date("Y-m-d H:i:s"),
                //'kit_name'=>$params['testKitName'],
                'kit_lot_no' => $params['testKitLotNo'],
                'kit_expiry_date' => ($params['testKitExpDate'] != '') ? $common->dbDateFormat($params['testKitExpDate']) : null,
                'vl_request_sent' => isset($params['sendVlsm']) ? $params['sendVlsm'] : 'no',
                'vl_request_sent_date_time' => (isset($params['sendVlsm']) && $params['sendVlsm'] == 'yes') ? $common->getDateTime() : null,
                'tester_name' => $params['testerName'],
                'vl_test_date' => ($params['vlTestDate'] != '') ? $common->dbDateFormat($params['vlTestDate']) : null,
                'vl_lab' => ($params['isVlLab'] != '') ? $params['isViralLabText'] : null,
                //'vl_result'=>($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                'sample_collection_date' => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate'] != '') ? $common->dbDateFormat($params['sampleCollectionDate']) : null,
                'sample_receipt_date' => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate'] != '') ? $common->dbDateFormat($params['sampleReceiptDate']) : null,
                'received_specimen_type' => $params['receivedSpecimenType'],
                'unique_id' => $this->randomizer(10),
                'testing_facility_type' => $params['testingModality'],
            );
            if ($params['vlLoadResult'] != '') {
                $data['vl_result'] = $params['vlLoadResult'];
                $data['vl_result_entry_date'] = date("Y-m-d H:i:s");
            } else if ($params['vlResultOption']) {
                $data['vl_result'] = htmlentities($params['vlResultOption']);
                $data['vl_result_entry_date'] = date("Y-m-d H:i:s");
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }

    public function fetchRecencyDetailsForPDF($recencyId)
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
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
                'sample_id'                         => $params['sampleId'],
                'patient_id'                        => $params['patientId'],
                'facility_id'                       => base64_decode($params['facilityId']),
                'testing_facility_id'               => ($params['testingFacilityId'] != '') ? base64_decode($params['testingFacilityId']) : null,
                'dob'                               => ($params['dob'] != '') ? $common->dbDateFormat($params['dob']) : null,
                'hiv_diagnosis_date'                => ($params['hivDiagnosisDate'] != '') ? $common->dbDateFormat($params['hivDiagnosisDate']) : null,
                'hiv_recency_test_date'             => (isset($params['hivRecencyTestDate']) && $params['hivRecencyTestDate'] != '') ? $common->dbDateFormat($params['hivRecencyTestDate']) : null,
                'recency_test_performed'            => $params['recencyTestPerformed'],
                'recency_test_not_performed'        => ($params['recencyTestPerformed'] == 'true') ? $params['recencyTestNotPerformed'] : null,
                'other_recency_test_not_performed'  => (isset($params['recencyTestPerformed']) && $params['recencyTestPerformed'] = 'other') ? $params['otherRecencyTestNotPerformed'] : null,
                'control_line'                      => (isset($params['controlLine']) && $params['controlLine'] != '') ? $params['controlLine'] : null,
                'positive_verification_line'        => (isset($params['positiveVerificationLine']) && $params['positiveVerificationLine'] != '') ? $params['positiveVerificationLine'] : null,
                'long_term_verification_line'       => (isset($params['longTermVerificationLine']) && $params['longTermVerificationLine'] != '') ? $params['longTermVerificationLine'] : null,
                'term_outcome'                      => $params['outcomeData'],
                'final_outcome'                     => $params['vlfinaloutcomeResult'],
                'gender'                            => $params['gender'],
                'age_not_reported'                  => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : no,
                'age'                               => ($params['age'] != '') ? $params['age'] : null,
                'marital_status'                    => $params['maritalStatus'],
                'residence'                         => $params['residence'],
                'education_level'                   => $params['educationLevel'],
                'risk_population'                   => base64_decode($params['riskPopulation']),
                'pregnancy_status'                  => $params['pregnancyStatus'],
                'current_sexual_partner'            => $params['currentSexualPartner'],
                'past_hiv_testing'                  => $params['pastHivTesting'],
                'last_hiv_status'                   => $params['lastHivStatus'],
                'patient_on_art'                    => $params['patientOnArt'],
                'test_last_12_month'                => $params['testLast12Month'],
                'location_one'                      => $params['location_one'],
                'location_two'                      => $params['location_two'],
                'location_three'                    => $params['location_three'],
                'exp_violence_last_12_month'        => $params['expViolence'],
                'notes'                             => $params['comments'],
                'kit_lot_no'                        => $params['testKitLotNo'],
                // 'kit_name'                          =>$params['testKitName'],
                'kit_expiry_date'                   => ($params['testKitExpDate'] != '') ? $common->dbDateFormat($params['testKitExpDate']) : null,
                'tester_name'                       => $params['testerName'],
                'form_saved_datetime'               => date('Y-m-d H:i:s'),
                'vl_test_date'                      => ($params['vlTestDate'] != '') ? $common->dbDateFormat($params['vlTestDate']) : null,
                // 'vl_result'                         =>($params['vlLoadResult']!='')?$params['vlLoadResult']:NULL,
                'sample_collection_date'            => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate'] != '') ? $common->dbDateFormat($params['sampleCollectionDate']) : null,
                'sample_receipt_date'               => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate'] != '') ? $common->dbDateFormat($params['sampleReceiptDate']) : null,
                'received_specimen_type'            => $params['receivedSpecimenType'],
                'testing_facility_type'             => $params['testingModality'],
                'modified_on'                       => $common->getDateTime(),
                'modified_by'                       => $logincontainer->userId
            );
            if ($params['vlLoadResult'] != '') {
                $data['vl_result'] = $params['vlLoadResult'];
            } else if ($params['vlResultOption']) {
                $data['vl_result'] = htmlentities($params['vlResultOption']);
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
        if ($updateResult > 0) {
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
        return $updateResult;
    }

    public function fetchAllRecencyListApi($params)
    {
        $common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
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
            $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                -$response['status'] = 'success';
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
        $common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $rececnyQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('hiv_recency_test_date', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
                ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
                ->join(array('u' => 'users'), 'u.user_id = r.added_by', array())
                ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));
            if ($uResult['role_code'] != 'admin') {
                $rececnyQuery = $rececnyQuery->where(array('u.auth_token' => $params['authToken']));
            }
            if (isset($params['start']) && isset($params['end'])) {
                $rececnyQuery = $rececnyQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) . "'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end'])) . "'"));
            }
            $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                $response['status'] = 'success';
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
        $common = new CommonService();
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
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
            $recencyQueryStr = $sql->getSqlStringForSqlObject($rececnyQuery);
            $recencyResult = $dbAdapter->query($recencyQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($recencyResult) > 0) {
                $response['status'] = 'success';
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
        $sql = new Sql($dbAdapter);

        $facilityDb = new FacilitiesTable($this->adapter);
        $riskPopulationDb = new RiskPopulationsTable($this->adapter);
        $globalDb = new GlobalConfigTable($this->adapter);
        $districtDb = new DistrictTable($this->adapter);
        $cityDb = new CityTable($this->adapter);
        $TestingFacilityTypeDb = new TestingFacilityTypeTable($this->adapter);
        $common = new CommonService();
        if (isset($params["form"])) {
            //check user status active or not
            $uQuery = $sql->select()->from('users')
                ->where(array('user_id' => $params["form"][0]['syncedBy']));
            $uQueryStr = $sql->getSqlStringForSqlObject($uQuery); // Get the string of the Sql, instead of the Select-instance
            $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
            if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
                $adminEmail = $globalDb->getGlobalValue('admin_email');
                $adminPhone = $globalDb->getGlobalValue('admin_phone');
                $response['message'] = 'Your password has expired or has been locked, please contact your administrator(' . $adminEmail . ' or ' . $adminPhone . ')';
                $response['status'] = 'failed';
                return $response;
            }
            $i = 1;
            foreach ($params["form"] as $key => $recency) {
                try {
                    if (isset($recency['sampleId']) && trim($recency['sampleId']) != "" || isset($recency['patientId']) && trim($recency['patientId']) != "") {
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

                        $syncedBy = $recency['syncedBy'];
                        $data = array(
                            'sample_id' => $recency['sampleId'],
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
                            'age_not_reported' => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : no,
                            'age' => ($params['age'] != '') ? $params['age'] : null,
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
                            'sync_by' => $recency['syncedBy'],
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

                        if ($recency['vlLoadResult'] != '') {
                            $data['vl_result'] = htmlentities($recency['vlLoadResult']);
                            $date['vl_result_entry_date'] = $recency['formSavedDateTime'];
                        }
                        if ($recency['finalOutcome'] != '') {
                            $data['final_outcome'] = $recency['finalOutcome'];
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
                            $response['syncData']['response'][$key] = 'success';
                        } else {
                            $response['syncData']['response'][$key] = 'failed';
                        }
                    }
                } catch (Exception $exc) {
                    error_log($exc->getMessage());
                    error_log($exc->getTraceAsString());
                }
                $i++;
            }
        } else {
            try {
                if (isset($params['sampleId']) && trim($params['sampleId']) != "") {
                    $syncedBy = $recency['syncedBy'];
                    $data = array(
                        'sample_id' => $params['sampleId'],
                        'patient_id' => $params['patientId'],
                        'facility_id' => (isset($params['facilityId']) && !empty($params['facilityId'])) ? ($params['facilityId']) : null,
                        'testing_facility_id' => (isset($params['testingFacility']) && !empty($params['testingFacility'])) ? ($params['testingFacility']) : null,
                        'control_line' => $params['ctrlLine'],
                        'positive_verification_line' => $params['positiveLine'],
                        'long_term_verification_line' => $params['longTermLine'],
                        'gender' => $params['gender'],
                        'latitude' => $params['latitude'],
                        'longitude' => $params['longitude'],
                        'age_not_reported' => (isset($params['ageNotReported']) && $params['ageNotReported'] != '') ? $params['ageNotReported'] : no,
                        'age' => ($params['age'] != '') ? $params['age'] : null,
                        'marital_status' => $params['maritalStatus'],
                        'residence' => $params['residence'],
                        'education_level' => $params['educationLevel'],
                        'risk_population' => $params['riskPopulation'],
                        'other_risk_population' => $params['otherriskPopulation'],
                        'term_outcome' => $params['recencyOutcome'],
                        'pregnancy_status' => $params['pregnancyStatus'],
                        'current_sexual_partner' => $params['currentSexualPartner'],
                        'past_hiv_testing' => $params['pastHivTesting'],
                        'last_hiv_status' => $params['lastHivStatus'],
                        'patient_on_art' => $params['patientOnArt'],
                        'test_last_12_month' => $params['testLast12Month'],
                        'location_one' => $params['locationOne'],
                        'location_two' => $params['locationTwo'],
                        'location_three' => $params['locationThree'],
                        'added_on' => $params['addedOn'],
                        'added_by' => $params['addedBy'],
                        'sync_by' => $params['syncedBy'],
                        'exp_violence_last_12_month' => $params['violenceLast12Month'],
                        'mac_no' => $params['macAddress'],
                        'cell_phone_number' => $params['phoneNumber'],
                        'recency_test_performed' => $params['testNotPerformed'],
                        'app_version' => $recency['appVersion'],
                        //'ip_address'=>$recency[''],
                        'form_initiation_datetime' => $params['formInitDateTime'],
                        'form_transfer_datetime' => date("Y-m-d H:i:s"),
                        //'kit_name' => $params['testKitName'],
                        'kit_lot_no' => $params['testKitLotNo'],
                        'tester_name' => $params['testerName'],
                        'unique_id' => $this->randomizer(10),
                        'vl_result' => $params['vlLoadResult'],
                        'sample_collection_date' => (isset($params['sampleCollectionDate']) && $params['sampleCollectionDate'] != '') ? $common->dbDateFormat($params['sampleCollectionDate']) : null,
                        'sample_receipt_date' => (isset($params['sampleReceiptDate']) && $params['sampleReceiptDate'] != '') ? $common->dbDateFormat($params['sampleReceiptDate']) : null,
                        'received_specimen_type' => $params['receivedSpecimenType'],
                        'testing_facility_type' => $params['testingModality'],
                    );

                    if (isset($params['vlTestDate']) && trim($params['vlTestDate']) != "") {
                        $data['vl_test_date'] = $common->dbDateFormat($params['vlTestDate']);
                    }

                    if (isset($params['hivRecencyTestDate']) && trim($params['hivDiagnosisDate']) != "") {
                        $data['hiv_diagnosis_date'] = $common->dbDateFormat($params['hivDiagnosisDate']);
                    }
                    if (isset($params['hivRecencyTestDate']) && trim($params['hivRecencyTestDate']) != "") {
                        $data['hiv_recency_test_date'] = $common->dbDateFormat($params['hivRecencyTestDate']);
                    }
                    if (isset($params['dob']) && trim($params['dob']) != "") {
                        $data['dob'] = $common->dbDateFormat($params['dob']);
                    }
                    if (isset($params['testKitExpDate']) && trim($params['testKitExpDate']) != "") {
                        $data['kit_expiry_date'] = $common->dbDateFormat($params['testKitExpDate']);
                    }

                    $this->insert($data);
                    $lastInsertedId = $this->lastInsertValue;
                    if ($lastInsertedId > 0) {
                        $response['syncData']['response'] = 'success';
                    } else {
                        $response['syncData']['response'] = 'failed';
                    }
                }
            } catch (Exception $exc) {
                error_log($exc->getMessage());
                error_log($exc->getTraceAsString());
            }
        }
        if ($syncedBy != '') {
            $response['syncCount']['response'] = $this->getTotalSyncCount($syncedBy);
        } else {
            $response['syncCount']['response'] = 0;
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
        $queryStr = $sql->getSqlStringForSqlObject($query);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where(array('r.term_outcome' => 'Assay Recent'));

        if (isset($params['province']) && $params['province'] != '') {
            $sQuery = $sQuery->where(array('f.province' => $params['province']));
        }
        if (isset($params['district']) && $params['district'] != '') {
            $sQuery = $sQuery->where(array('f.district' => $params['district']));
        }
        if (isset($params['city']) && $params['city'] != '') {
            $sQuery = $sQuery->where(array('f.city' => $params['city']));
        }
        if (isset($params['facility']) && $params['facility'] != '') {
            $sQuery = $sQuery->where(array('r.vl_test_date' => $params['vlTestDate']));
        }
        if (isset($params['onloadData']) && $params['onloadData'] == 'yes') {
            $sQuery = $sQuery->where(array('r.vl_result is null OR r.vl_result=""'));
        }
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

        $rResult['withTermOutcome'] = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $sQueryTerm = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'vl_lab', 'vl_request_sent_date_time', 'vl_test_date', 'vl_request_sent', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where('r.vl_result is null')
            ->where('r.vl_request_sent != "no"');

        if (isset($params['province']) && $params['province'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('f.province' => $params['province']));
        }
        if (isset($params['district']) && $params['district'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('f.district' => $params['district']));
        }
        if (isset($params['city']) && $params['city'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('f.city' => $params['city']));
        }
        if (isset($params['facility']) && $params['facility'] != '') {
            $sQueryTerm = $sQueryTerm->where(array('r.vl_test_date' => $params['vlTestDate']));
        }
        if (isset($params['onloadData']) && $params['onloadData'] == 'yes') {
            $sQueryTerm = $sQueryTerm->where(array('r.vl_result is null OR r.vl_result=""'));
        }
        $sQueryStrTerm = $sql->getSqlStringForSqlObject($sQueryTerm);
        // echo $sQueryStrTerm;die;
        $rResult['withOutTermOutcome'] = $dbAdapter->query($sQueryStrTerm, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        return $rResult;
    }

    public function updateVlSampleResult($params)
    {
        //\Zend\Debug\Debug::dump($params);die;
        $common = new CommonService();
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

            $this->update($data, array('recency_id' => str_replace('vlResultOption', '', $sampleVlResultId[$key])));
        }
    }

    public function fetchAllRecencyResultWithVlList($parameters)
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'control_line', 'positive_verification_line', 'long_term_verification_line', 'age', 'gender', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('province' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('district' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('city' => $parameters['locationThree']));
            }
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        $queryContainer->exportRecentResultDataQuery = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%RITA Recent%')));

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

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
            $row[] = ucwords(str_replace('_', ' ', $aRow['received_specimen_type']));
            $row[] = ucwords($aRow['testing_facility_name']);
            $row[] = $common->humanDateFormat($aRow['vl_test_date']);
            $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
                         <a class="btn btn-primary" href="javascript:void(0)" onclick="generateRecentPdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>
                         </div>';
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchAllLtResult($parameters)
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

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'control_line', 'positive_verification_line', 'long_term_verification_line', 'age', 'gender', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%Long Term%')));

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
        if ($parameters['fName'] != '') {
            $sQuery->where(array('r.facility_id' => $parameters['fName']));
        }
        if ($parameters['locationOne'] != '') {
            $sQuery = $sQuery->where(array('province' => $parameters['locationOne']));
            if ($parameters['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('district' => $parameters['locationTwo']));
            }
            if ($parameters['locationThree'] != '') {
                $sQuery = $sQuery->where(array('city' => $parameters['locationThree']));
            }
        }
        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        $queryContainer->exportLongtermDataQuery = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

        // echo $sQueryStr;die;
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('recency_id', 'hiv_recency_test_date', 'sample_id', 'term_outcome', 'final_outcome', 'vl_result', 'vl_test_date'))
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array(new \Zend\Db\Sql\Predicate\Like('final_outcome', '%Long Term%')));

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

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
            $row[] = '<div class="btn-group btn-group-sm" role="group" aria-label="Small Horizontal Primary">
            <a class="btn btn-primary" href="javascript:void(0)" onclick="generateLTermPdf(' . $aRow['recency_id'] . ')"><i class="far fa-file-pdf"></i> PDF</a>
            </div>';
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchTatReportAPI($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        //check the user is active or not
        $uQuery = $sql->select()->from(array('u' => 'users'))->columns(array('user_id', 'status'))
            ->join(array('rl' => 'roles'), 'u.role_id = rl.role_id', array('role_code'))
            ->where(array('auth_token' => $params['authToken']));
        $uQueryStr = $sql->getSqlStringForSqlObject($uQuery);
        $uResult = $dbAdapter->query($uQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        if (isset($uResult['status']) && $uResult['status'] == 'inactive') {
            $response["status"] = "fail";
            $response["message"] = "Your status is Inactive!";
        } else if (isset($uResult['status']) && $uResult['status'] == 'active') {
            $sQuery = $sql->select()->from(array('r' => 'recency'))
                ->columns(array(
                    'sample_id', 'final_outcome', "hiv_recency_test_date" => new Expression("DATE_FORMAT(DATE(hiv_recency_test_date), '%d-%b-%Y')"), 'vl_test_date' => new Expression("DATE_FORMAT(DATE(vl_test_date), '%d-%b-%Y')"), 'vl_result_entry_date' => new Expression("DATE_FORMAT(DATE(vl_result_entry_date), '%d-%b-%Y')"),
                    "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_test_date))) AS DECIMAL (10))"),
                ))
                ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_test_date!="" AND vl_test_date!=""'))
                ->group('recency_id');
            if (isset($params['start']) && isset($params['end'])) {
                $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) . "'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end'])) . "'"));
            }
            if ($uResult['role_code'] != 'admin') {
                $sQuery = $sQuery->where(array('u.auth_token' => $params['authToken']));
            }
            $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
            $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            if (count($rResult) > 0) {
                $response['status'] = 'success';
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
            ->columns(array(
                'sample_id', 'final_outcome', "hiv_recency_test_date", 'vl_test_date', 'vl_result_entry_date', 'sample_collection_date', 'sample_receipt_date', 'received_specimen_type',
                "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_test_date))) AS DECIMAL (10))"),
            ))

            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_test_date!="" AND vl_test_date!=""'))
            ->group('recency_id');
        // if(isset($params['start']) && isset($params['end'])){
        //     $sQuery = $sQuery->where(array("r.hiv_recency_test_date >='" . date("Y-m-d", strtotime($params['start'])) ."'", "r.hiv_recency_test_date <='" . date("Y-m-d", strtotime($params['end']))."'"));
        // }

        if ($parameters['testingFacility'] != '') {
            $sQuery->where(array('r.testing_facility_id' => $parameters['testingFacility']));
        }
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
        $queryContainer->exportTatQuery = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);

        // echo $sQueryStr;die;data
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);

        /* Data set length after filtering */
        $sQuery->reset('limit');
        $sQuery->reset('offset');
        $tQueryStr = $sql->getSqlStringForSqlObject($sQuery); // Get the string of the Sql, instead of the Select-instance
        $aResultFilterTotal = $dbAdapter->query($tQueryStr, $dbAdapter::QUERY_MODE_EXECUTE);
        $iFilteredTotal = count($aResultFilterTotal);

        /* Total data set length */
        $iQuery = $sql->select()->from(array('r' => 'recency'))
            ->columns(array(
                'sample_id', 'final_outcome', "hiv_recency_test_date", 'vl_test_date', 'vl_result_entry_date',
                "diffInDays" => new Expression("CAST(ABS(AVG(TIMESTAMPDIFF(DAY,vl_result_entry_date,hiv_recency_test_date))) AS DECIMAL (10))"),
            ))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->where(array('vl_result_entry_date!="" AND vl_result_entry_date!="0000-00-00 00:00:00" AND hiv_recency_test_date!="" AND vl_test_date!=""'))
            ->group('recency_id');

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
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
            $row[] = date('d-M-Y', strtotime($aRow['vl_result_entry_date']));
            $row[] = $aRow['diffInDays'];
            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchSampleResult($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array('sample_id', 'patient_id', 'recency_id', 'vl_test_date', 'hiv_recency_test_date', 'term_outcome', 'vl_result', 'final_outcome'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where(array('vl_result!="" AND vl_result is not null AND mail_sent_status is null'));
        if ($params['locationOne'] != '') {
            $sQuery = $sQuery->where(array('province' => $params['locationOne']));
            if ($params['locationTwo'] != '') {
                $sQuery = $sQuery->where(array('district' => $params['locationTwo']));
            }
            if ($params['locationThree'] != '') {
                $sQuery = $sQuery->where(array('city' => $params['locationThree']));
            }
        }
        if ($params['facilityId'] != '') {
            $sQuery = $sQuery->where(array('r.facility_id' => $params['facilityId']));
        }

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function fetchEmailSendResult($params)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);

        $sQuery = $sql->select()->from(array('r' => 'recency'))
            ->join(array('f' => 'facilities'), 'f.facility_id = r.facility_id', array('facility_name'))
            ->where("recency_id IN(" . $params['selectedSampleId'] . ")");

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $rResult;
    }

    public function updateEmailSendResult($params)
    {
        $tempDb = new \Application\Model\TempMailTable($this->adapter);

        $config = new \Zend\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');

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
            foreach ($emailFormField['to'] as $recencyId) {
                $this->update(array('mail_sent_status' => 'yes'), array('recency_id' => $recencyId));
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
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

        if ((in_array(strtolower($fOutCome['vl_result']), $this->vlFailOptionArray))) {
            $data['final_outcome'] = 'Inconclusive';
        } else if ((in_array(strtolower($fOutCome['vl_result']), $this->vlResultOptionArray))) {
            $data['final_outcome'] = 'Long Term';
        } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] > 1000) {
            $data['final_outcome'] = 'RITA Recent';
        } else if (strpos($fOutCome['term_outcome'], 'Recent') !== false && $fOutCome['vl_result'] <= 1000) {
            $data['final_outcome'] = 'Long Term';
        }
        $this->update($data, array('recency_id' => $recencyId));
    }

    //refer updateOutcome Function
    public function updateTermOutcome($outcome)
    {
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
            $this->update(array('term_outcome' => 'Invalid â€“ Please Verify'), array('recency_id' => $recencyId));
        } else if ($controlLine == 'present' && $positiveControlLine == 'absent' && $longControlLine == 'absent') {
            $this->update(array('term_outcome' => 'Assay Negative'), array('recency_id' => $recencyId));
        } else if ($controlLine == 'present' && $positiveControlLine == 'present' && $longControlLine == 'absent') {
            $this->update(array('term_outcome' => 'Assay Recent'), array('recency_id' => $recencyId));
        } else if ($controlLine == 'present' && $positiveControlLine == 'present' && $longControlLine == 'present') {
            $this->update(array('term_outcome' => 'Long Term'), array('recency_id' => $recencyId));
        }
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

        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
        $fResult = $dbAdapter->query($fQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        if (count($fResult) > 0) {
            foreach ($fResult as $data) {
                $fQuery = $sql1->select()->from(array('vl' => 'vl_request_form'))
                    ->columns(array('result', 'sample_code'))
                    ->where(array('sample_code' => $data['sample_id']));

                $fQueryStr = $sql1->getSqlStringForSqlObject($fQuery);
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
                                                                                WHEN (((r.sample_collection_date is NOT NULL AND r.sample_collection_date !=''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Samples Pending to be Tested" => new Expression("SUM(CASE
                                                                                WHEN (((r.term_outcome is NULL OR r.term_outcome =''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Samples Tested" => new Expression("SUM(CASE
                                                                                WHEN (((r.term_outcome is NOT NULL AND r.term_outcome !=''))) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Assay Recent" => new Expression("SUM(CASE
                                                                                    WHEN ((term_outcome ='Assay Recent' OR term_outcome ='assay recent')) THEN 1
                                                                                    ELSE 0
                                                                                    END)"),
                    "Long Term" => new Expression("SUM(CASE
                                                                                WHEN ((term_outcome='Long Term' OR term_outcome='long term')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Assay Negative" => new Expression("SUM(CASE
                                                                                WHEN ((term_outcome='Assay Negative' OR term_outcome='assay negative')) THEN 1
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
                                                                                WHEN ((final_outcome='RITA Recent' OR final_outcome='RITA recent')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Long Term Final" => new Expression("SUM(CASE
                                                                                WHEN ((final_outcome='Long Term' OR final_outcome='long term')) THEN 1
                                                                                ELSE 0
                                                                                END)"),
                    "Inconclusive" => new Expression("SUM(CASE
                                                                                WHEN ((final_outcome='Inconclusive' OR final_outcome='inconclusive')) THEN 1
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
        if ($params['facilityName'] != '') {
            $rQuery = $rQuery->where(array('r.facility_id' => $params['facilityName']));
        }
        $queryContainer->exportWeeklyDataQuery = $rQuery;
        $rQueryStr = $sql->getSqlStringForSqlObject($rQuery);
        $fResult = $dbAdapter->query($rQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
        return $fResult;
    }

    public function fetchAllRecencyResult($parameters)
    {
        /* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $queryContainer = new Container('query');
        $common = new CommonService();
        $general = new CommonService();
        $aColumns = array('f.facility_name', 'ft.facility_name');
        $orderColumns = array('f.facility_name', 'ft.facility_name', 'totalSamples', 'samplesReceived', 'samplesRejected', 'samplesTestBacklog', 'samplesTestVlPending', 'samplesTestedRecency', 'samplesTestedViralLoad', 'samplesFinalOutcome');

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
        $totalSamples = array();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
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
                                                                 WHEN (((r.final_outcome is NOT NULL) )) THEN 1
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
                    "ritaRecent" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='RITA Recent')) THEN 1
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

        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        $queryContainer->exportRecencyDataResultDataQuery = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo $sQueryStr;die;

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

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        foreach ($rResult as $aRow) {
            $ltper = 0;
            $arper = 0;
            if (trim($aRow['samplesFinalLongTerm']) != "") {
                $ltper = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (trim($aRow['ritaRecent']) != "") {
                $arper = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
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
            $row[] = $aRow['samplesFinalLongTerm'];
            $row[] = $ltper;
            $row[] = $aRow['ritaRecent'];
            $row[] = $arper;

            $output['aaData'][] = $row;
        }
        return $output;
    }

    public function fetchRecencyAllDataCount($parameters)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $general = new CommonService();
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function fetchFinalOutcomeChart($parameters)
    {
        $dbAdapter = $this->adapter;

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
            $sQuery = $sQuery->where(array("r.added_on >='" . $start_date . "'", "r.added_on <='" . $end_date . "'"));
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
                                                                    WHEN (((r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !=''))) THEN 1
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
                                                                            WHEN (((r.added_on is NOT NULL AND r.added_on !=''))) THEN 1
                                                                            ELSE 0
                                                                        END)"),
                        "samplesTested" => new Expression("SUM(CASE
                                                                        WHEN (((r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !=''))) THEN 1
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            ->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'))
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'))
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->where(array('r.final_outcome' => 'RITA Recent'))
            //->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !='')")
            ->order('total DESC')
            ->group('d.district_name');

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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //\Zend\Debug\Debug::dump($sQueryStr);die;
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
                        "15T24" => new Expression("(SUM(CASE WHEN (r.age >= '15' AND r.age<=24) THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "25T34" => new Expression("(SUM(CASE WHEN (r.age >= '25' AND r.age<=34) THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "35T44" => new Expression("(SUM(CASE WHEN (r.age >= '35' AND r.age<=44) THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                        "45+" => new Expression("(SUM(CASE WHEN (r.age>='45') THEN 1 ELSE 0 END) / COUNT(*)) * 100"),
                    )
                );
        } else {
            $sQuery = $sQuery
                ->columns(
                    array(
                        'gender',
                        "total" => new Expression('COUNT(*)'),
                        "15T24" => new Expression("(SUM(CASE WHEN (r.age >= '15' AND r.age<=24) THEN 1 ELSE 0 END))"),
                        "25T34" => new Expression("(SUM(CASE WHEN (r.age >= '25' AND r.age<=34) THEN 1 ELSE 0 END))"),
                        "35T44" => new Expression("(SUM(CASE WHEN (r.age >= '35' AND r.age<=44) THEN 1 ELSE 0 END))"),
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo($sQueryStr);die;
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
            ->where("(r.hiv_recency_test_date is NOT NULL AND r.hiv_recency_test_date !='')")
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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
        $common = new CommonService();
        $general = new CommonService();
        $aColumns = array('d.district_name');
        $orderColumns = array('d.district_name', 'totalSamples', 'samplesReceived', 'samplesRejected', 'samplesTestBacklog', 'samplesTestVlPending', 'samplesTestedRecency', 'samplesTestedViralLoad', 'samplesFinalOutcome', 'samplesFinalLongTerm', '', 'ritaRecent');

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
        $totalSamples = array();
        $sQuery = $sql->select()->from(array('r' => 'recency'))
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
                                                                 WHEN (((r.final_outcome is NOT NULL) )) THEN 1
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
                    "ritaRecent" => new Expression("SUM(CASE
                                                                 WHEN ((r.final_outcome='RITA Recent')) THEN 1
                                                                 ELSE 0
                                                                 END)"),

                )
            )
            ->join(array('f' => 'facilities'), 'r.facility_id = f.facility_id', array('facility_name'))
            //->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'))
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.location_two');

        if (isset($sWhere) && $sWhere != "") {
            $sQuery->where($sWhere);
        }
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

        if ($parameters['tOutcome'] != '') {
            $sQuery->where(array('term_outcome' => $parameters['tOutcome']));
        }

        if ($parameters['finalOutcome'] != '') {
            $sQuery->where(array('final_outcome' => $parameters['finalOutcome']));
        }

        if (isset($sOrder) && $sOrder != "") {
            $sQuery->order($sOrder);
        }

        if (isset($sLimit) && isset($sOffset)) {
            $sQuery->limit($sLimit);
            $sQuery->offset($sOffset);
        }
        $queryContainer->exportDistrictwiseRecencyResult = $sQuery;
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo $sQueryStr;die;

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
            //->join(array('ft' => 'facilities'), 'ft.facility_id = r.testing_facility_id', array('testing_facility_name' => 'facility_name'), 'left')
            ->join(array('p' => 'province_details'), 'p.province_id = r.location_one', array('province_name'), 'left')
            ->join(array('d' => 'district_details'), 'd.district_id = r.location_two', array('district_name'), 'left')
            ->join(array('c' => 'city_details'), 'c.city_id = r.location_three', array('city_name'), 'left')
            ->group('r.location_two');

        $iQueryStr = $sql->getSqlStringForSqlObject($iQuery); // Get the string of the Sql, instead of the Select-instance
        $iResult = $dbAdapter->query($iQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

        $output = array(
            "sEcho" => intval($parameters['sEcho']),
            "iTotalRecords" => count($iResult),
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array(),
        );

        foreach ($rResult as $aRow) {
            $ltper = 0;
            $arper = 0;
            if (trim($aRow['samplesFinalLongTerm']) != "") {
                $ltper = round((($aRow['samplesFinalLongTerm'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
            }
            if (trim($aRow['ritaRecent']) != "") {
                $arper = round((($aRow['ritaRecent'] / $aRow['samplesFinalOutcome']) * 100), 2) . '%';
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
            $row[] = $aRow['samplesFinalLongTerm'];
            $row[] = $ltper;
            $row[] = $aRow['ritaRecent'];
            $row[] = $arper;

            $output['aaData'][] = $row;
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        //echo($sQueryStr);die;
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
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

        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        $rResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
        return $rResult;
    }



    public function UpdatePdfUpdatedDateDetails($recenyId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $fQuery = $sql->select()->from(array('r' => 'recency'))
            ->where(array('(recency_id="' . $recenyId . '" )'));
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
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
        $fQueryStr = $sql->getSqlStringForSqlObject($fQuery);
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
                'vl_result'             => $params['result'],
                'vl_test_date'          => date('Y-m-d', strtotime($params['sampleTestedDatetime'])),
                'vl_result_entry_date'  => $common->getDateTime()
            );
            $results =  $this->update($data, array('sample_id' => $params['sampleId']));
        }
        if (isset($results) && $results > 0) {
            $responseStatus['status'] = 'success';
        } else {
            $responseStatus['status'] = 'fail';
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        // die($sQueryStr);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }

    public function getDataBySampleId($sId)
    {
        $dbAdapter = $this->adapter;
        $sql = new Sql($dbAdapter);
        $sQuery = $sql->select()->from(array('r' => 'recency'))->columns(array(
            'recency_id', 'facility_id', 'sample_id', 'patient_id', 'sample_collection_date', 'vl_result', 'received_specimen_type'
        ))->where(array('sample_id' => $sId));
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->current();
    }

    public function saveRequestFlag($rId)
    {
        $common = new CommonService();
        $this->update(array(
            'vl_request_sent'           => 'yes',
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
        $sQueryStr = $sql->getSqlStringForSqlObject($sQuery);
        return $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
    }
}
