
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1

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