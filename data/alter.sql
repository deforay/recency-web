
-- Role Table Insert Thanaseelan 04-09-2018
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'super Admin', 'SA', 'active');
-- User Table
ALTER TABLE `users` ADD CONSTRAINT `role_forign_key` FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
INSERT INTO `users` (`user_id`, `user_name`, `role_id`, `email`, `app_password`, `server_password`, `alt_email`, `mobile`, `alt_mobile`, `job_responsibility`, `comments`, `status`) VALUES (NULL, 'admin', '1', 'admin@gmail.com', '123', '123', 'sadmin@gmail.com', '9994027557', '9876543210', NULL, NULL, 'active');

-- Normal user add on role table Thanaseelan 05-09-2018
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'user', 'NU', 'active');

-- Recency Table Alter Thanaseelan 05-09-2018
ALTER TABLE `recency` CHANGE `facility` `facility_id` INT(11) NOT NULL;
ALTER TABLE `recency` CHANGE `added_by` `added_by` INT(11) NULL DEFAULT NULL;

-- sarabvanan 05-sep-2018
ALTER TABLE `users` ADD `auth_token` VARCHAR(255) NULL DEFAULT NULL AFTER `server_password`;

-- Thanaseelan 07-09-2018
ALTER TABLE `recency` ADD `dob` DATE NULL DEFAULT NULL AFTER `hiv_recency_result`, ADD `gender` VARCHAR(255) NULL DEFAULT NULL AFTER `dob`;

-- Thanaseelan 10-09-2018
CREATE TABLE `risk_populations` (
 `rp_id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 PRIMARY KEY (`rp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1

ALTER TABLE `recency` ADD `marital_status` VARCHAR(255) NULL DEFAULT NULL AFTER `longitude`, ADD `residence` VARCHAR(255) NULL DEFAULT NULL AFTER `marital_status`, ADD `education_levl` VARCHAR(255) NULL DEFAULT NULL AFTER `residence`, ADD `risk_population` VARCHAR(255) NULL DEFAULT NULL AFTER `education_levl`, ADD `past_hiv_testing` VARCHAR(255) NULL DEFAULT NULL AFTER `risk_population`;

ALTER TABLE `recency` ADD `test_last_12_month` VARCHAR(255) NULL DEFAULT NULL AFTER `past_hiv_testing`;

ALTER TABLE `recency` CHANGE `education_levl` `education_level` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

ALTER TABLE `recency` CHANGE `sample_id` `sample_id` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `recency` CHANGE `patient_id` `patient_id` VARCHAR(255) NULL DEFAULT NULL;


--Thana 12-sep-2018
ALTER TABLE `recency` ADD `pregnancy_status` VARCHAR(255) NULL DEFAULT NULL AFTER `risk_population`, ADD `current_sexual_partner` VARCHAR(255) NULL DEFAULT NULL AFTER `pregnancy_status`;
ALTER TABLE `recency` ADD `age` VARCHAR(255) NULL DEFAULT NULL AFTER `dob`;
ALTER TABLE `recency` ADD `location_one` VARCHAR(255) NULL DEFAULT NULL AFTER `test_last_12_month`, ADD `location_two` VARCHAR(255) NULL DEFAULT NULL AFTER `location_one`, ADD `location_three` VARCHAR(255) NULL DEFAULT NULL AFTER `location_two`;
CREATE TABLE `global_config` (
 `config_id` int(11) NOT NULL AUTO_INCREMENT,
 `display_name` varchar(255) DEFAULT NULL,
 `global_name` varchar(255) DEFAULT NULL,
 `global_value` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`config_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4_general_ci;

--Vivek 26-sept-2018

CREATE TABLE `recency_app`.`province_details` ( `province_id` INT(11) NOT NULL AUTO_INCREMENT , `province_name` VARCHAR(255) NOT NULL , PRIMARY KEY (`province_id`)) ENGINE = InnoDB;
CREATE TABLE `recency_app`.`district_details` ( `district_id` INT(11) NOT NULL AUTO_INCREMENT , `province_id` INT(11) NOT NULL , `district_name` VARCHAR(255) NOT NULL , PRIMARY KEY (`district_id`)) ENGINE = InnoDB;
CREATE TABLE `recency_app`.`city_details` ( `city_id` INT(11) NOT NULL AUTO_INCREMENT , `district_id` INT(11) NOT NULL , `city_name` VARCHAR(255) NOT NULL , PRIMARY KEY (`city_id`)) ENGINE = InnoDB;

ALTER TABLE district_details ADD CONSTRAINT district_provience_map FOREIGN KEY(province_id) REFERENCES province_details(province_id) ON UPDATE RESTRICT ON DELETE RESTRICT;
ALTER TABLE city_details ADD CONSTRAINT city_district_map FOREIGN KEY(district_id) REFERENCES district_details(district_id) ON UPDATE RESTRICT ON DELETE RESTRICT;


-- saravanan 27-sep-2018
ALTER TABLE `facilities` ADD `city` VARCHAR(255) NULL DEFAULT NULL AFTER `district`;

-- saravanan 03-Oct-2018
ALTER TABLE `recency` CHANGE `hiv_recency_result` `long_term_verification_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `recency` ADD `control_line` VARCHAR(255) NULL DEFAULT NULL AFTER `hiv_recency_test_date`, ADD `positive_verification_line` VARCHAR(255) NULL DEFAULT NULL AFTER `control_line`;

-- Thanaseelan 03-Oct-2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Recency Mandatory Fields', 'mandatory_fields', NULL);

-- Thanaseelan 09-Oct-2018
ALTER TABLE `recency` ADD `last_hiv_status` VARCHAR(255) NULL DEFAULT NULL AFTER `past_hiv_testing`, ADD `patient_on_art` VARCHAR(255) NULL DEFAULT NULL AFTER `last_hiv_status`;

-- Vivek 10-Oct-2018
ALTER TABLE `recency` ADD `other_risk_population` VARCHAR(255) NULL DEFAULT NULL AFTER `risk_population`;

-- saravanan 12-oct-2018
ALTER TABLE `recency` ADD `exp_violence_last_12_month` VARCHAR(255) NULL DEFAULT NULL AFTER `test_last_12_month`, ADD `mac_no` VARCHAR(255) NULL DEFAULT NULL AFTER `exp_violence_last_12_month`, ADD `cell_phone_number` VARCHAR(255) NULL DEFAULT NULL AFTER `mac_no`, ADD `ip_address` VARCHAR(255) NULL DEFAULT NULL AFTER `cell_phone_number`, ADD `form_initiation_datetime` DATETIME NULL DEFAULT NULL AFTER `ip_address`, ADD `form_transfer_datetime` DATETIME NULL DEFAULT NULL AFTER `form_initiation_datetime`;

-- saravanan 15-oct-2018
ALTER TABLE `recency` ADD `term_outcome` VARCHAR(255) NULL DEFAULT NULL AFTER `long_term_verification_line`;

ALTER TABLE `recency` ADD `recency_test_performed` VARCHAR(255) NULL DEFAULT NULL AFTER `hiv_recency_test_date`;

-- vivek 16-oct-2018
ALTER TABLE `recency` CHANGE `control_line` `control_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `positive_verification_line` `positive_verification_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `long_term_verification_line` `long_term_verification_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;
ALTER TABLE `users` ADD `web_access` VARCHAR(255) NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `recency` ADD `recency_test_not_performed` VARCHAR(255) NULL DEFAULT NULL AFTER `recency_test_performed`;
ALTER TABLE `recency` ADD `other_recency_test_not_performed` VARCHAR(255) NULL DEFAULT NULL AFTER `recency_test_not_performed`;

ALTER TABLE `recency` ADD `notes` VARCHAR(255) NULL DEFAULT NULL AFTER `location_three`;


-- vivek 25th-oct-2018
CREATE TABLE `recency_app`.`quality_check_test` ( `qc_test_id` INT(11) NOT NULL AUTO_INCREMENT , `qc_test_date` DATE NULL DEFAULT NULL , `qc_sample_id` VARCHAR(255) NULL DEFAULT NULL , `reference_result` VARCHAR(255) NULL DEFAULT NULL , `kit_lot_no` VARCHAR(255) NULL DEFAULT NULL , `kit_expiry_date` DATE NULL DEFAULT NULL , `hiv_recency_test_date` DATE NULL DEFAULT NULL , `control_line` VARCHAR(255) NULL DEFAULT NULL , `positive_verification_line` VARCHAR(255) NULL DEFAULT NULL , `long_term_verification_line` VARCHAR(255) NULL DEFAULT NULL , `tester_name` VARCHAR(255) NULL DEFAULT NULL , `added_on` DATETIME NULL DEFAULT NULL , `added_by` INT(11) NULL DEFAULT NULL , PRIMARY KEY (`qc_test_id`)) ENGINE = InnoDB;


-- vivek 26th-oct-2018
ALTER TABLE `quality_check_test` ADD `term_outcome` VARCHAR(255) NULL DEFAULT NULL AFTER `long_term_verification_line`;
ALTER TABLE `quality_check_test` ADD `comment` VARCHAR(255) NULL DEFAULT NULL AFTER `tester_name`;

-- vivek 26th-oct-2018
ALTER TABLE `quality_check_test` ADD `recency_test_performed` VARCHAR(255) NULL DEFAULT NULL AFTER `kit_expiry_date`, ADD `recency_test_not_performed_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `recency_test_performed`, ADD `other_recency_test_not_performed_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `recency_test_not_performed_reason`;

-- vivek 8th-Nov-2018

ALTER TABLE `recency` ADD `kit_name` VARCHAR(40) NULL DEFAULT NULL AFTER `added_by`;

ALTER TABLE `quality_check_test` ADD `kit_name` VARCHAR(40) NULL DEFAULT NULL AFTER `reference_result`;


-- saravanan 10-nov-2018
ALTER TABLE `recency` ADD `app_version` VARCHAR(255) NULL DEFAULT NULL AFTER `notes`;
ALTER TABLE `quality_check_test` ADD `app_version` VARCHAR(255) NULL DEFAULT NULL AFTER `comment`;

-- saravanan 12-nov-2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Admin Email', 'admin_email', 'admin@gmail.com'), (NULL, 'Admin Phone', 'admin_phone', '111111111');

INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'Viral Load Testing Site', 'VLTS', 'active');

-- saravanan 13-nov-2018
ALTER TABLE `recency` ADD `vl_result` VARCHAR(255) NULL DEFAULT NULL AFTER `kit_expiry_date`, ADD `final_outcome` VARCHAR(255) NULL DEFAULT NULL AFTER `vl_result`;

-- saravanan 14-nov-2018
ALTER TABLE `users` ADD `qc_sync_in_days` VARCHAR(255) NULL DEFAULT NULL AFTER `comments`;
-- saravanan 21-nov-2018
ALTER TABLE `recency` ADD `sync_by` INT NULL DEFAULT NULL AFTER `added_by`;
ALTER TABLE `quality_check_test` ADD `form_initiation_datetime` DATETIME NULL DEFAULT NULL AFTER `app_version`, ADD `form_transfer_datetime` DATETIME NULL DEFAULT NULL AFTER `form_initiation_datetime`, ADD `sync_by` INT NULL DEFAULT NULL AFTER `form_transfer_datetime`;

-- Thanaseelan 27 Nov, 2018
ALTER TABLE `recency` ADD `vl_test_date` DATE NULL DEFAULT NULL AFTER `vl_result`;

ALTER TABLE `recency` ADD `vl_result_entry_date` DATETIME NULL DEFAULT NULL AFTER `vl_test_date`;


-- Thanaseelan 03 Dec, 2018
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Recency Display Fields', 'display_fields', NULL);


-- Sasi & vivek 13 Dec, 2018
ALTER TABLE `recency` ADD `form_saved_datetime` DATETIME NULL DEFAULT NULL AFTER `form_transfer_datetime`;

-- saravanan 14-dec-2018
UPDATE `global_config` SET `display_name` = 'Hide Fields' WHERE `global_config`.`config_id` = 7;


-- Amit 20 Dec 2018

ALTER TABLE `recency` ADD `unique_id` VARCHAR(255) NOT NULL FIRST;
ALTER TABLE `quality_check_test` ADD `unique_id` VARCHAR(255) NOT NULL FIRST;
ALTER TABLE `quality_check_test` ADD `form_saved_datetime` DATETIME NULL DEFAULT NULL AFTER `form_transfer_datetime`;

-- Amit 14 Jan 2019

ALTER TABLE `recency` CHANGE `vl_result_entry_date` `vl_result_entry_date` DATETIME NULL DEFAULT NULL;


-- saravanan 11-jan-2019
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Send Results(Email configuration)', 'email_id', 'zfexample@gmail.com');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Send Results(Email password)', 'email_password', 'zaq12345');

-- saravanan 18-jan-2019
CREATE TABLE `temp_mail` (
  `temp_id` int(11) NOT NULL,
  `message` mediumtext,
  `from_mail` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) DEFAULT NULL,
  `cc` varchar(500) DEFAULT NULL,
  `bcc` varchar(500) DEFAULT NULL,
  `subject` mediumtext,
  `from_full_name` varchar(255) DEFAULT NULL,
  `attachment` text,
  `status` varchar(255) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `temp_mail`
  ADD PRIMARY KEY (`temp_id`),
  ADD UNIQUE KEY `temp_id` (`temp_id`);


ALTER TABLE `temp_mail`
  MODIFY `temp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `recency` ADD `mail_sent_status` VARCHAR(255) NULL DEFAULT NULL AFTER `unique_id`;
-- saravanan 22-jan-2019
ALTER TABLE `recency` ADD `upload_result_datetime` DATETIME NULL DEFAULT NULL AFTER `vl_test_date`;

-- vivek 24-jan-2019
CREATE TABLE `facility_type` ( `facility_type_id` INT(11) NOT NULL AUTO_INCREMENT , `facility_type_name` VARCHAR(255) NULL DEFAULT NULL , `facility_type_status` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`facility_type_id`)) ENGINE = InnoDB;

-- vivek 24-jan-2019
INSERT INTO `facility_type` (`facility_type_id`, `facility_type_name`, `facility_type_status`) VALUES (NULL, 'Normal', 'active'), (NULL, 'Testing', 'active');

-- vivek 24-jan-2019
 ALTER TABLE `facilities` ADD `facility_type_id` INT NULL DEFAULT NULL AFTER `facility_id`;

-- saravanan 28-jan-2019
ALTER TABLE `recency` ADD `testing_facility_id` INT NULL DEFAULT NULL AFTER `facility_id`;

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Announcement', 'admin_message', 'test');

ALTER TABLE `recency` ADD `mail_sent_status` VARCHAR(255) NULL DEFAULT NULL AFTER `unique_id`;

-- Amit 18 Jan 2019
UPDATE `global_config` SET `display_name` = 'Display Fields' WHERE `global_config`.`config_id` = 7;
--sathish 07 Mar 2019
 ALTER TABLE `recency` ADD `sample_collection_date` DATE NULL AFTER `facility_id`, ADD `sample_receipt_date` DATE NULL AFTER `sample_collection_date`, ADD `received_specimen_type` VARCHAR(255) NULL AFTER `sample_receipt_date`;
--sathish 25 Mar 2019
 ALTER TABLE `recency` ADD `testing_facility_type` VARCHAR(255) NULL DEFAULT NULL AFTER `received_specimen_type`;
ALTER TABLE `recency` CHANGE `testing_facility_type` `testing_facility_type` INT(11) NULL DEFAULT NULL;
CREATE TABLE `testing_facility_type` (
  `testing_facility_type_id` int(11) NOT NULL,
  `testing_facility_type_name` varchar(255) DEFAULT NULL,
  `testing_facility_type_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `testing_facility_type`
  ADD PRIMARY KEY (`testing_facility_type_id`);

  ALTER TABLE `testing_facility_type`
  MODIFY `testing_facility_type_id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `testing_facility_type` (`testing_facility_type_id`, `testing_facility_type_name`, `testing_facility_type_status`) VALUES
(1, 'VCT', 'active'),
(2, 'OPD', 'active'),
(3, 'PMTCT/ ANC', 'active'),
(4, 'STI Clinic', 'active'),
(5, 'Index', 'active'),
(6, 'Mobile', 'active'),
(7, 'PITC', 'active'),
(8, 'VMMC', 'active');

UPDATE `global_config` SET `display_name` = 'Location Level One (Province)' WHERE `global_config`.`config_id` = 1;
UPDATE `global_config` SET `display_name` = 'Location Level Two (District)' WHERE `global_config`.`config_id` = 2;
UPDATE `global_config` SET `display_name` = 'Location Level Three (City)' WHERE `global_config`.`config_id` = 3;

UPDATE `global_config` SET `display_name` = 'Technical Support Email' WHERE `global_config`.`config_id` = 5;
UPDATE `global_config` SET `display_name` = 'Technical Support Phone' WHERE `global_config`.`config_id` = 6;

INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Technical Support Name', 'technical_support_name', 'test');
--sathish 26 Mar 2019
ALTER TABLE `testing_facility_type` CHANGE `testing_facility_type` `testing_facility_type_name` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

CREATE TABLE `manage_columns_map` (
  `map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `manage_columns` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `manage_columns_map`
  ADD PRIMARY KEY (`map_id`);

  ALTER TABLE `manage_columns_map`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT;
--sathish 28 Mar 2019

ALTER TABLE `quality_check_test` ADD `testing_facility_id` INT NULL DEFAULT NULL AFTER `added_by`;
--sathish 29 Mar 2019
ALTER TABLE `recency` CHANGE `hiv_recency_date` `hiv_recency_test_date` DATE NULL DEFAULT NULL;
ALTER TABLE `quality_check_test` CHANGE `hiv_recency_date` `hiv_recency_test_date` DATE NULL DEFAULT NULL;

--sathish 10 May 2019
CREATE TABLE `tester_information` (
  `test_id` int(11) NOT NULL,
  `reference_result` varchar(255) DEFAULT NULL,
  `kit_lot_no` varchar(255) DEFAULT NULL,
  `kit_expiry_date` date DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `tester_information`
  ADD PRIMARY KEY (`test_id`);

ALTER TABLE `tester_information`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT;

--ilahir 14-May-2019
ALTER TABLE `quality_check_test` ADD `final_result` VARCHAR(255) NULL DEFAULT NULL AFTER `testing_facility_id`;


UPDATE quality_check_test SET `final_result`='pass' WHERE `reference_result`='long_term_sample' AND `term_outcome`='Long Term';
UPDATE quality_check_test SET `final_result`='pass' WHERE `reference_result`='preliminary_recent_sample' AND `term_outcome`='Assay Recent';
UPDATE quality_check_test SET `final_result`='pass' WHERE `reference_result`='hiv_negative_sample' AND `term_outcome`='Assay HIV Negative';

--sathish 20 May 2019
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'Management', 'MGMT', 'active');
ALTER TABLE `recency` ADD `result_printed_on` DATETIME NULL DEFAULT NULL AFTER `testing_facility_type`;
--sathish 22 May 2019
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Mail Host', 'mail_host', 'smtp.gmail.com'), (NULL, 'Mail Port', 'mail_port', '587');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Mail SSL', 'mail_ssl', 'tls'), (NULL, 'Mail Auth', 'mail_auth', 'login');
--sathish 24 May 2019
RENAME TABLE `tester_information` TO `test_kit_information`;
ALTER TABLE `test_kit_information` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `status`, ADD `added_by` INT(11) NULL DEFAULT NULL AFTER `added_on`;

-- Thanaseelan 30-May-2019 Created for Between vlsm and recnecy (API VL Lab test request)
ALTER TABLE `facilities` ADD `is_vl_lab` VARCHAR(255) NULL DEFAULT 'no' AFTER `facility_type_id`;

-- Thanaseelan 04-Jun-2019 Created for VL Test Request send or not?
ALTER TABLE `recency` ADD `vl_request_sent` VARCHAR(255) NOT NULL DEFAULT 'no' AFTER `kit_expiry_date`;

-- Thanaseelan 04-Jun-2019 Created for VL Test Request send date time
ALTER TABLE `recency` ADD `vl_request_sent_date_time` DATETIME NULL DEFAULT NULL AFTER `vl_request_sent`;

-- Thanaseelan 04-Jun-2019 Created for showing the pending vl result from vlsm reference
ALTER TABLE `recency` ADD `vl_lab` VARCHAR(255) NULL DEFAULT NULL AFTER `vl_request_sent_date_time`;



-- Amit 26 June 2019
UPDATE `recency` SET `term_outcome` = 'Invalid' where `term_outcome` like 'Invalid%'

-- Thanaseelan 21-Aug-2019
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'Manager', 'manager', 'active');
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Recency to VLSM sync', 'recency_to_vlsm_sync', 'no');

-- vivek 22nd june
CREATE TABLE `qc_samples` ( `qc_sample_id` INT(11) NOT NULL AUTO_INCREMENT , `qc_sample_no` VARCHAR(255) NULL DEFAULT NULL , `qc_sample_status` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`qc_sample_id`)) ENGINE = InnoDB;
ALTER TABLE `qc_samples` ADD `added_on` DATETIME NULL DEFAULT NULL AFTER `qc_sample_status`, ADD `added_by` INT NULL DEFAULT NULL AFTER `added_on`;
-- Thanaseelan 26-Sep-2019
ALTER TABLE `recency` ADD `age_not_reported` VARCHAR(255) NOT NULL DEFAULT 'no' AFTER `term_outcome`;
-- Thanaseelan 18-Oct-2019
CREATE TABLE `recency_change_trails` (
 `unique_id` varchar(255) DEFAULT NULL,
 `mail_sent_status` varchar(255) DEFAULT NULL,
 `recency_trails_id` int(11) NOT NULL AUTO_INCREMENT,
 `recency_id` int(11) DEFAULT NULL,
 `sample_id` varchar(255) DEFAULT NULL,
 `patient_id` varchar(255) DEFAULT NULL,
 `facility_id` int(11) DEFAULT NULL,
 `sample_collection_date` date DEFAULT NULL,
 `sample_receipt_date` date DEFAULT NULL,
 `received_specimen_type` varchar(255) DEFAULT NULL,
 `testing_facility_type` int(11) DEFAULT NULL,
 `result_printed_on` datetime DEFAULT NULL,
 `sample_received_date` date DEFAULT NULL,
 `testing_facility_id` int(11) DEFAULT NULL,
 `hiv_diagnosis_date` date DEFAULT NULL,
 `hiv_recency_test_date` date DEFAULT NULL,
 `recency_test_performed` varchar(255) DEFAULT NULL,
 `recency_test_not_performed` varchar(255) DEFAULT NULL,
 `other_recency_test_not_performed` varchar(255) DEFAULT NULL,
 `control_line` varchar(255) DEFAULT NULL,
 `positive_verification_line` varchar(255) DEFAULT NULL,
 `long_term_verification_line` varchar(255) DEFAULT NULL,
 `term_outcome` varchar(255) DEFAULT NULL,
 `age_not_reported` varchar(255) NOT NULL DEFAULT 'no',
 `dob` date DEFAULT NULL,
 `age` varchar(255) DEFAULT NULL,
 `gender` varchar(255) DEFAULT NULL,
 `latitude` varchar(255) DEFAULT NULL,
 `longitude` varchar(255) DEFAULT NULL,
 `marital_status` varchar(255) DEFAULT NULL,
 `residence` varchar(255) DEFAULT NULL,
 `education_level` varchar(255) DEFAULT NULL,
 `risk_population` varchar(255) DEFAULT NULL,
 `other_risk_population` varchar(255) DEFAULT NULL,
 `pregnancy_status` varchar(255) DEFAULT NULL,
 `current_sexual_partner` varchar(255) DEFAULT NULL,
 `past_hiv_testing` varchar(255) DEFAULT NULL,
 `last_hiv_status` varchar(255) DEFAULT NULL,
 `patient_on_art` varchar(255) DEFAULT NULL,
 `test_last_12_month` varchar(255) DEFAULT NULL,
 `exp_violence_last_12_month` varchar(255) DEFAULT NULL,
 `mac_no` varchar(255) DEFAULT NULL,
 `cell_phone_number` varchar(255) DEFAULT NULL,
 `ip_address` varchar(255) DEFAULT NULL,
 `form_initiation_datetime` datetime DEFAULT NULL,
 `form_transfer_datetime` datetime DEFAULT NULL,
 `form_saved_datetime` datetime DEFAULT NULL,
 `comment` text,
 `kit_lot_no` varchar(255) DEFAULT NULL,
 `kit_expiry_date` date DEFAULT NULL,
 `vl_request_sent` varchar(255) DEFAULT NULL,
 `vl_request_sent_date_time` datetime DEFAULT NULL,
 `vl_lab` varchar(255) DEFAULT NULL,
 `vl_result` varchar(255) DEFAULT NULL,
 `vl_test_date` date DEFAULT NULL,
 `upload_result_datetime` datetime DEFAULT NULL,
 `vl_result_entry_date` datetime DEFAULT NULL,
 `final_outcome` varchar(255) DEFAULT NULL,
 `tester_name` varchar(255) DEFAULT NULL,
 `location_one` varchar(255) DEFAULT NULL,
 `location_two` varchar(255) DEFAULT NULL,
 `location_three` varchar(255) DEFAULT NULL,
 `notes` varchar(255) DEFAULT NULL,
 `app_version` varchar(255) DEFAULT NULL,
 `added_on` datetime DEFAULT NULL,
 `added_by` int(11) DEFAULT NULL,
 `sync_by` int(11) DEFAULT NULL,
 `kit_name` varchar(40) DEFAULT NULL,
 `trail_created_on` datetime DEFAULT NULL,
 `trail_created_by` int(11) DEFAULT NULL,
 PRIMARY KEY (`recency_trails_id`),
 UNIQUE KEY `unique_id` (`unique_id`,`sample_id`),
 KEY `facility_foreign_key` (`facility_id`),
 KEY `user_foreign_key` (`added_by`),
 KEY `recency_id` (`recency_id`),
 CONSTRAINT `recency_change_trails_ibfk_1` FOREIGN KEY (`recency_id`) REFERENCES `recency` (`recency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Thanaseelan 21-Oct-2019
ALTER TABLE `recency` ADD `modified_on` DATETIME NULL DEFAULT NULL AFTER `kit_name`;
ALTER TABLE `recency` ADD `modified_by` INT(11) NULL DEFAULT NULL AFTER `modified_on`;
ALTER TABLE `recency` ADD `invalid_control_line` VARCHAR(255) NULL DEFAULT NULL AFTER `modified_by`, ADD `invalid_verification_line` VARCHAR(255) NULL DEFAULT NULL AFTER `invalid_control_line`, ADD `invalid_longterm_line` VARCHAR(255) NULL DEFAULT NULL AFTER `invalid_verification_line`;
-- In Clone Table
ALTER TABLE `recency_change_trails` ADD `modified_on` DATETIME NULL DEFAULT NULL AFTER `kit_name`, ADD `modified_by` INT(11) NULL DEFAULT NULL AFTER `modified_on`, ADD `invalid_control_line` VARCHAR(255) NULL DEFAULT NULL AFTER `modified_by`, ADD `invalid_verification_line` VARCHAR(255) NULL DEFAULT NULL AFTER `invalid_control_line`, ADD `invalid_longterm_line` VARCHAR(255) NULL DEFAULT NULL AFTER `invalid_verification_line`;
-- Thanaseelan creating the new role name as remote user 18-Nov-2019
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'Remote Order User', 'remote_order_user', 'active');
ALTER TABLE recency_change_trails DROP INDEX unique_id;

-- Thanaseelan traking final_outcome and term_outcome and remote_order 11-Dec-2019
ALTER TABLE `recency` ADD `assay_outcome_updated_by` INT(11) NULL DEFAULT NULL AFTER `invalid_longterm_line`, ADD `assay_outcome_updated_on` DATETIME NULL AFTER `assay_outcome_updated_by`, ADD `final_outcome_updated_by` INT(11) NULL DEFAULT NULL AFTER `assay_outcome_updated_on`, ADD `final_outcome_updated_on` DATETIME NULL AFTER `final_outcome_updated_by`, ADD `remote_order` VARCHAR(50) NOT NULL DEFAULT 'no' AFTER `final_outcome_updated_on`;
-- And recency_change_trails table
ALTER TABLE `recency_change_trails` ADD `assay_outcome_updated_by` INT(11) NULL DEFAULT NULL AFTER `invalid_longterm_line`, ADD `assay_outcome_updated_on` DATETIME NULL AFTER `assay_outcome_updated_by`, ADD `final_outcome_updated_by` INT(11) NULL DEFAULT NULL AFTER `assay_outcome_updated_on`, ADD `final_outcome_updated_on` DATETIME NULL AFTER `final_outcome_updated_by`, ADD `remote_order` VARCHAR(50) NOT NULL DEFAULT 'no' AFTER `final_outcome_updated_on`;

-- prasath

ALTER TABLE `recency` ADD `sample_id_year_prefix` INT(11) NOT NULL AFTER `sample_id`;
update recency SET sample_id_year_prefix=19;
ALTER TABLE `recency` ADD `sample_id_string_prefix` varchar(11) NOT NULL AFTER `sample_id`;
update recency SET sample_id_string_prefix='RT';
ALTER TABLE `recency` ADD `sample_prefix_id` INT(6) UNSIGNED ZEROFILL NOT NULL AFTER `sample_id`;
update recency set sample_prefix_id=recency_id;


ALTER TABLE `recency_change_trails` ADD `sample_id_year_prefix` INT(11) NOT NULL AFTER `sample_id`;
update recency_change_trails SET sample_id_year_prefix=19;
ALTER TABLE `recency_change_trails` ADD `sample_id_string_prefix` varchar(11) NOT NULL AFTER `sample_id`;
update recency_change_trails SET sample_id_string_prefix='RT';
ALTER TABLE `recency_change_trails` ADD `sample_prefix_id` INT(6) UNSIGNED ZEROFILL NOT NULL AFTER `sample_id`;
update recency_change_trails set sample_prefix_id=recency_id;

ALTER TABLE `users` ADD `secret_key` VARCHAR(255) NOT NULL AFTER `auth_token`;

--prasath 14-Jan-2020

CREATE TABLE `event_log` (
 `event_id` int(11) NOT NULL AUTO_INCREMENT,
 `actor` int(11) NOT NULL,
 `subject` varchar(255) DEFAULT NULL,
 `event_type` varchar(255) DEFAULT NULL,
 `action` varchar(255) DEFAULT NULL,
 `resource_name` varchar(255) DEFAULT NULL,
 `added_on` datetime DEFAULT NULL,
 PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Thanaseelan 20 Feb 2020 For syn option from vlsm lis_vl_result and lis_vl_test_data

ALTER TABLE `recency` ADD `lis_vl_result` VARCHAR(255) NULL DEFAULT NULL AFTER `vl_test_date`, ADD `lis_vl_test_date` DATE NULL DEFAULT NULL AFTER `lis_vl_result`;
ALTER TABLE `recency` ADD `lis_vl_result_entry_date` DATE NULL DEFAULT NULL AFTER `lis_vl_test_date`;

-- Amit 28 Feb 2020

UPDATE `roles` SET `role_name` = 'System Admin' WHERE `roles`.`role_id` = 1;
UPDATE `roles` SET `role_name` = 'Recency Testing Hub' WHERE `roles`.`role_id` = 2;
UPDATE `roles` SET `role_name` = 'Manager (view only)' WHERE `roles`.`role_id` = 5;

-- Amit 4 Mar 2020

ALTER TABLE `recency_change_trails` ADD `lis_vl_result` VARCHAR(255) NULL DEFAULT NULL AFTER `vl_test_date`, ADD `lis_vl_test_date` DATE NULL DEFAULT NULL AFTER `lis_vl_result`;
ALTER TABLE `recency_change_trails` ADD `lis_vl_result_entry_date` DATE NULL DEFAULT NULL AFTER `lis_vl_test_date`;


-- Amit 7 Mar 2020

CREATE TABLE `manifests` (
 `manifest_id` int(11) NOT NULL AUTO_INCREMENT,
 `manifest_code` varchar(255) NOT NULL,
 `added_by` int(11) NOT NULL,
 `added_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`manifest_id`),
 UNIQUE KEY `manifest_code` (`manifest_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `recency` ADD `manifest_id` INT NULL DEFAULT NULL AFTER `sample_collection_date`, ADD `manifest_code` VARCHAR(255) NULL DEFAULT NULL AFTER `manifest_id`;
-- 09-Mar-2020 Thanaseelan
INSERT INTO `global_config` (`config_id`, `display_name`, `global_name`, `global_value`) VALUES (NULL, 'Header', 'header', '');


-- Amit 10 mar 2020

ALTER TABLE `manifests` ADD `testing_site` INT NOT NULL AFTER `manifest_code`;
ALTER TABLE `recency_change_trails` ADD `manifest_id` INT NULL DEFAULT NULL AFTER `facility_id`, ADD `manifest_code` VARCHAR(255) NULL DEFAULT NULL AFTER `manifest_id`;

-- Thana 28-Sep-2022
ALTER TABLE `users` ADD `hash_algorithm` VARCHAR(256) NOT NULL DEFAULT 'sha1' AFTER `secret_key`;



-- Jeyabanu 16 Jan 2023
CREATE TABLE `user_login_history` (
 `history_id` int NOT NULL AUTO_INCREMENT,
 `user_id` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 `login_id` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
 `login_attempted_datetime` datetime DEFAULT NULL,
 `login_status` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 `ip_address` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 `browser` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 `operating_system` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 PRIMARY KEY (`history_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ilahir 21-Mar-2023
CREATE TABLE `resources` (
  `resource_id` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD UNIQUE KEY `resource_id` (`resource_id`);


CREATE TABLE `privileges` (
  `resource_id` varchar(255) NOT NULL DEFAULT '',
  `privilege_name` varchar(255) NOT NULL DEFAULT '',
  `display_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `privileges`
  ADD PRIMARY KEY (`resource_id`,`privilege_name`),
  ADD UNIQUE KEY `resource_id_2` (`resource_id`,`privilege_name`),
  ADD KEY `resource_id` (`resource_id`);


ALTER TABLE `privileges`
  ADD CONSTRAINT `privileges_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`);


INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Roles', 'Manage Roles');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Roles', 'index', 'Access'), ('Application\\Controller\\Roles', 'add', 'Add');

-- Brindha 22-Mar-2023
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Roles', 'edit', 'Edit');


INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\GlobalConfig', 'GlobalConfig');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\GlobalConfig', 'index', 'Access'), ('Application\\Controller\\GlobalConfig', 'edit', 'Edit');
UPDATE `resources` SET `display_name` = 'Manage GlobalConfig' WHERE `resources`.`resource_id` = 'Application\\Controller\\GlobalConfig';

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\User', 'Manage User');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\User', 'index', 'Access'), ('Application\\Controller\\User', 'add', 'Add'), ('Application\\Controller\\User', 'edit', 'Edit');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Facilities', 'Manage Facilities');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Facilities', 'index', 'Access'), ('Application\\Controller\\Facilities', 'add', 'Add'), ('Application\\Controller\\Facilities', 'edit', 'Edit');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Province', 'Manage Province');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Province', 'index', 'Access'), ('Application\\Controller\\Province', 'add', 'Add'), ('Application\\Controller\\Province', 'edit', 'Edit');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\District', 'Manage District');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\District', 'index', 'Access'), ('Application\\Controller\\District', 'add', 'Add'), ('Application\\Controller\\District', 'edit', 'Edit');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\City', 'Manage City');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\City', 'index', 'Access'), ('Application\\Controller\\City', 'add', 'Add'), ('Application\\Controller\\City', 'edit', 'Edit');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Settings', 'Settings');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Settings', 'index', 'Access'), ('Application\\Controller\\Settings', 'add', 'Add'), ('Application\\Controller\\Settings', 'edit', 'Edit');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES  ('Application\\Controller\\Settings', 'add-sample', 'Add Sample'), ('Application\\Controller\\Settings', 'edit-sample', 'Edit Sample');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Recency', 'All Data');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Recency', 'index', 'Access'), ('Application\\Controller\\Recency', 'add', 'Add'), ('Application\\Controller\\Recency', 'edit', 'Edit');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Recency', 'export-recency', 'Export Recency'), ('Application\\Controller\\Recency', 'generate-pdf', 'Generate Pdf');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\QualityCheck', 'QualityCheck');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\QualityCheck', 'index', 'Access'), ('Application\\Controller\\QualityCheck', 'add', 'Add'), ('Application\\Controller\\QualityCheck', 'edit', 'Edit'), ('Application\\Controller\\QualityCheck', 'export-qc-data', 'Export QualityCheck');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\VlData', 'All Pending Results');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'index', 'Access'), ('Application\\Controller\\VlData', 'get-sample-data', 'Sample Data'), ('Application\\Controller\\VlData', 'upload-result', 'Upload VL');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'email-result', 'Email Result');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'recent-infection', 'Recent Infection'),('Application\\Controller\\VlData', 'export-r-infected-data', 'Export Recent Infection'),('Application\\Controller\\VlData', 'lt-infection', 'Long Term Infection'),('Application\\Controller\\VlData', 'export-long-term-infected-data', 'Export Long Term Infection');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'tat-report', 'TAT Report'),('Application\\Controller\\VlData', 'export-tat-report', 'Export TAT Report'),('Application\\Controller\\VlData', 'weekly-report', 'Weekly Report'),('Application\\Controller\\VlData', 'export-weekly-report', 'Export Weekly Report');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'qc-report', 'QualityCheck Report'),('Application\\Controller\\VlData', 'age-wise-infection-report', 'Age wise Infection Report'),('Application\\Controller\\VlData', 'export-modality', 'Export Modality');

-- Brindha 23-Mar-2023
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Index', 'Dashboard');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Index', 'index', 'Access Laboratory'),('Application\\Controller\\Index', 'export-recency-data', 'Export Recency'), ('Application\\Controller\\Index', 'analysis-dashboard', 'Access Analysis'), ('Application\\Controller\\Index', 'quality-control-dashboard', 'Access QualityControl');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\PrintResults', 'Print Results');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\PrintResults', 'index', 'Access');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Manifests', 'Manifests');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Manifests', 'index', 'Access');

INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\Monitoring', 'Monitoring');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\Monitoring', 'all-user-login-history', 'All UserLogin History'), ('Application\\Controller\\Monitoring', 'audit-trail', 'Audit Trail'), ('Application\\Controller\\Monitoring', 'user-activity-log', 'User Activity Log');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlData', 'update-vl-sample-result', 'Update VL Sample');

ALTER TABLE `recency` CHANGE `vl_request_sent` `vl_request_sent` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'not-required';

-- Brindha 04-April-2023
UPDATE `recency` set `received_specimen_type` = '1' WHERE `received_specimen_type` like 'plasma';
UPDATE `recency` set `received_specimen_type` = '2' WHERE `received_specimen_type` like 'whole_blood';
ALTER TABLE `recency` CHANGE `received_specimen_type` `received_specimen_type` INT NULL DEFAULT NULL;

-- Brindha 05-April-2023
ALTER TABLE `recency` ADD `lis_vl_sample_code` TEXT NULL DEFAULT NULL AFTER `vl_lab`;
ALTER TABLE `audit_recency` ADD `lis_vl_sample_code` TEXT NULL DEFAULT NULL AFTER `vl_lab`;

-- Brindha 06-April-2023
CREATE TABLE `r_sample_types` (
  `sample_id` int NOT NULL AUTO_INCREMENT,
  `sample_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `status` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL,
  `data_sync` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`sample_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `r_sample_types` (`sample_id`, `sample_name`, `status`, `updated_datetime`, `data_sync`) VALUES ('1', 'Plasma', 'active', NULL, '0'), ('2', 'Whole Blood', 'active', NULL, '0');

-- Brindha 10-April-2023
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ManifestsController', 'edit', 'Edit'), ('Application\\Controller\\ManifestsController', 'genarate-manifest', 'Generate Manifest');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ManifestsController', 'add', 'Add');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlDataController', 'email-result-pdf', 'Email Result Pdf');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\VlDataController', 'download-result-pdf', 'Download Email Result Pdf');

-- Brindha 12-April-2023
ALTER TABLE `recency_change_trails` ADD `lis_vl_sample_code` TEXT NULL DEFAULT NULL AFTER `vl_lab`;

ALTER TABLE `recency` ADD `vl_lab_id` INT NULL DEFAULT NULL AFTER `vl_lab`;
ALTER TABLE `audit_recency` ADD `vl_lab_id` INT NULL DEFAULT NULL AFTER `vl_lab`;
ALTER TABLE `recency_change_trails` ADD `vl_lab_id` INT NULL DEFAULT NULL AFTER `vl_lab`;


-- Cron Run cmd
save-request  - ./vendor/bin/laminas vlsm-send-requests
fetch-results - ./vendor/bin/laminas vlsm-receive-results
send-mail     - ./vendor/bin/laminas send-mail
system-alerts - ./vendor/bin/laminas system-alerts

-- Brindha 14-July-2023
INSERT INTO `resources` (`resource_id`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'Reports');

DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'recent-infection';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'export-r-infected-data';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'lt-infection';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'export-long-term-infected-data';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'tat-report';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'export-tat-report';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'weekly-report';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'export-weekly-report';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'qc-report';
DELETE FROM `privileges` WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'age-wise-infection-report';
DELETE FROM `privileges` WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'export-modality';
DELETE FROM `privileges` WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'email-result';
DELETE FROM `privileges` WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'email-result-pdf';
DELETE FROM `privileges` WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataController' AND `privileges`.`privilege_name` = 'download-result-pdf';

DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataControllerController' AND `privileges`.`privilege_name` = 'download-result-pdf';
DELETE FROM privileges WHERE `privileges`.`resource_id` = 'Application\\Controller\\VlDataControllerController' AND `privileges`.`privilege_name` = 'email-result-pdf';

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'recent-infection', 'Recent Infection');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'export-r-infected-data', 'Export Recent Infection');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'lt-infection', 'Long Term Infection');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'export-long-term-infected-data', 'Export Long Term Infection');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'tat-report', 'TAT Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'export-tat-report', 'Export TAT Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'weekly-report', 'Weekly Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'export-weekly-report', 'Export Weekly Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'qc-report', 'QualityCheck Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'age-wise-infection-report', 'Age wise Infection Report');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\ReportsController', 'export-modality', 'Export Modality');

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\RecencyController', 'email-result', 'Email Result');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\RecencyController', 'email-result-pdf', 'Email Result Pdf');
INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\RecencyController', 'download-result-pdf', 'Download Email Result Pdf');

-- Brindha 20-July-2023
DELETE FROM `resources` WHERE `resources`.`resource_id` = 'Application\\Controller\\LoginController';

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\UserController', 'edit-profile', 'Edit Profile');

-- Brindha 25-July-2023
CREATE TABLE `system_alerts` (
  `alert_id` int NOT NULL AUTO_INCREMENT,
  `alert_text` text NOT NULL,
  `facility_id` int DEFAULT NULL,
  `lab_id` int DEFAULT NULL,
  `alert_type` varchar(256) NOT NULL,
  `alert_status` varchar(256) NOT NULL,
  `alerted_on` datetime NOT NULL,
  `updated_datetime` datetime NOT NULL,
  PRIMARY KEY (`alert_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `privileges` (`resource_id`, `privilege_name`, `display_name`) VALUES ('Application\\Controller\\MonitoringController', 'system-alerts', 'system-alerts');

UPDATE `privileges` SET `display_name` = 'Download Email Result' WHERE `privileges`.`resource_id` = 'Application\\Controller\\RecencyController' AND `privileges`.`privilege_name` = 'download-result-pdf';
UPDATE `privileges` SET `display_name` = 'Export Long Term' WHERE `privileges`.`resource_id` = 'Application\\Controller\\ReportsController' AND `privileges`.`privilege_name` = 'export-long-term-infected-data';
