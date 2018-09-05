
-- Role Table Insert Thanaseelan 04-09-2018
INSERT INTO `roles` (`role_id`, `role_name`, `role_code`, `role_status`) VALUES (NULL, 'super Admin', 'SA', 'active');
-- User Table
ALTER TABLE `users` ADD CONSTRAINT `role_forign_key` FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
INSERT INTO `users` (`user_id`, `user_name`, `role_id`, `email`, `app_password`, `server_password`, `alt_email`, `mobile`, `alt_mobile`, `job_responsibility`, `comments`, `status`) VALUES (NULL, 'admin', '1', 'admin@gmail.com', '123', '123', 'sadmin@gmail.com', '9994027557', '9876543210', NULL, NULL, 'active');

-- sarabvanan 05-sep-2018
ALTER TABLE `users` ADD `auth_token` VARCHAR(255) NULL DEFAULT NULL AFTER `server_password`;