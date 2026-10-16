CREATE DATABASE IF NOT EXISTS `uiu_canteen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uiu_canteen`;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_id` (`role_id`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`) VALUES
('customer'),
('shop_owner'),
('admin'),
('delivery_person')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- NOTE:
-- Main admin account can be created manually in phpMyAdmin or inserted later by a database admin.
-- Example:
-- INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `status`) VALUES
-- ('System Admin', 'admin@uiu.edu', '$2y$10$...', (SELECT `id` FROM `roles` WHERE `name` = 'admin'), 'active');
