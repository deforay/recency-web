
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
