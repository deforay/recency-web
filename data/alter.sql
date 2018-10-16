
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
ALTER TABLE `recency` ADD `control_line` VARCHAR(255) NULL DEFAULT NULL AFTER `hiv_recency_date`, ADD `positive_verification_line` VARCHAR(255) NULL DEFAULT NULL AFTER `control_line`;

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

ALTER TABLE `recency` ADD `recency_test_performed` VARCHAR(255) NULL DEFAULT NULL AFTER `hiv_recency_date`;

ALTER TABLE `recency` CHANGE `control_line` `control_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `positive_verification_line` `positive_verification_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL, CHANGE `long_term_verification_line` `long_term_verification_line` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;