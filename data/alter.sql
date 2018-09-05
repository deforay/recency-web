
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
ALTER TABLE `recency` ADD CONSTRAINT `user_foreign_key` FOREIGN KEY (`added_by`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `recency` ADD CONSTRAINT `facility_forign_key` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`facility_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;