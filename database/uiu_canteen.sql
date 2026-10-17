-- UIU Canteen Food Delivery System - Complete Database Schema
-- Created for MySQL 5.7+

CREATE DATABASE IF NOT EXISTS `uiu_canteen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uiu_canteen`;

-- =====================================================
-- 1. ROLES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. USERS TABLE - All users (customer, shop_owner, admin, delivery_person)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_id` (`role_id`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CATEGORIES TABLE - Food categories
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. SHOPS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `shops` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `owner_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `category_id` INT DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `logo_image` VARCHAR(255) DEFAULT NULL,
    `opening_time` TIME DEFAULT NULL,
    `closing_time` TIME DEFAULT NULL,
    `status` ENUM('active', 'warning', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_shops_owner_id` (`owner_id`),
    KEY `idx_shops_category_id` (`category_id`),
    KEY `idx_shops_status` (`status`),
    CONSTRAINT `fk_shops_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_shops_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. FOODS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `foods` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `shop_id` INT NOT NULL,
    `category_id` INT DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `preparation_time` INT DEFAULT NULL COMMENT 'Preparation time in minutes',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_foods_shop_id` (`shop_id`),
    KEY `idx_foods_category_id` (`category_id`),
    KEY `idx_foods_available` (`is_available`),
    CONSTRAINT `fk_foods_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_foods_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. CART TABLE - Customer shopping cart
-- =====================================================
CREATE TABLE IF NOT EXISTS `carts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_carts_customer` (`customer_id`),
    CONSTRAINT `fk_carts_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. CART ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `cart_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `shop_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cart_items_cart_id` (`cart_id`),
    KEY `idx_cart_items_food_id` (`food_id`),
    CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cart_items_food` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `delivery_address` VARCHAR(255) DEFAULT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'accepted', 'preparing', 'ready', 'picked_up', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_customer_id` (`customer_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_created_at` (`created_at`),
    CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `shop_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order_id` (`order_id`),
    KEY `idx_order_items_food_id` (`food_id`),
    KEY `idx_order_items_shop_id` (`shop_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_food` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. DELIVERY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `deliveries` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `delivery_person_id` INT DEFAULT NULL,
    `status` ENUM('pending', 'assigned', 'picked_up', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    `accepted_time` DATETIME DEFAULT NULL,
    `pickup_time` DATETIME DEFAULT NULL,
    `delivered_time` DATETIME DEFAULT NULL,
    `earning` DECIMAL(10,2) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_deliveries_order_id` (`order_id`),
    KEY `idx_deliveries_delivery_person_id` (`delivery_person_id`),
    KEY `idx_deliveries_status` (`status`),
    CONSTRAINT `fk_deliveries_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_deliveries_delivery_person` FOREIGN KEY (`delivery_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. REPORTS TABLE - Customer complaints about shops/food
-- =====================================================
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `shop_id` INT NOT NULL,
    `category` VARCHAR(100) NOT NULL COMMENT 'e.g., Quality, Late Delivery, Wrong Item',
    `description` TEXT NOT NULL,
    `evidence_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('submitted', 'under_review', 'resolved', 'rejected') NOT NULL DEFAULT 'submitted',
    `admin_response` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reports_customer_id` (`customer_id`),
    KEY `idx_reports_shop_id` (`shop_id`),
    KEY `idx_reports_order_id` (`order_id`),
    KEY `idx_reports_status` (`status`),
    CONSTRAINT `fk_reports_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. ADMIN ACTIONS TABLE - Track admin actions on shops
-- =====================================================
CREATE TABLE IF NOT EXISTS `admin_actions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `admin_id` INT NOT NULL,
    `shop_id` INT NOT NULL,
    `action_type` ENUM('warning', 'fine', 'suspend', 'ban', 'activate', 'remove') NOT NULL,
    `fine_amount` DECIMAL(10,2) DEFAULT NULL,
    `admin_note` TEXT,
    `action_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_actions_admin_id` (`admin_id`),
    KEY `idx_admin_actions_shop_id` (`shop_id`),
    KEY `idx_admin_actions_action_type` (`action_type`),
    CONSTRAINT `fk_admin_actions_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_admin_actions_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. RATINGS TABLE - Customer ratings for shops and food
-- =====================================================
CREATE TABLE IF NOT EXISTS `ratings` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `target_type` ENUM('shop', 'food') NOT NULL,
    `target_id` INT NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_ratings_customer_target` (`customer_id`, `target_type`, `target_id`),
    KEY `idx_ratings_target` (`target_type`, `target_id`),
    KEY `idx_ratings_customer_id` (`customer_id`),
    CONSTRAINT `fk_ratings_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT ROLES
-- =====================================================
INSERT INTO `roles` (`name`, `description`) VALUES
('customer', 'Regular customer who places orders'),
('shop_owner', 'Shop owner who manages food menu and orders'),
('admin', 'Administrator who manages shops and reports'),
('delivery_person', 'Delivery person who delivers orders')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- =====================================================
-- INSERT CATEGORIES
-- =====================================================
INSERT INTO `categories` (`name`, `description`, `status`) VALUES
('Fast Food', 'Quick service meals like burgers, fries, hot dogs', 'active'),
('Biryani', 'Traditional biryani dishes', 'active'),
('Chinese', 'Chinese cuisine - fried rice, noodles, etc.', 'active'),
('Desserts', 'Cakes, pastries, ice cream', 'active'),
('Drinks', 'Beverages - tea, coffee, juice, soft drinks', 'active')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- =====================================================
-- CLEANUP STRAY TEST USERS
-- Prevent fake/demo customer entries from appearing in the app
-- =====================================================
DELETE FROM `users` WHERE `email` LIKE '%zakir%' OR `full_name` IN ('Zakir Hossen', 'Hossen');

-- =====================================================
-- INSERT SAMPLE USERS
-- Password: "password123" hashed with PHP PASSWORD_DEFAULT
-- =====================================================

-- Admin User (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Admin User', 'admin@uiu-canteen.local', '+8801711111111', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 3, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Shop Owner 1 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Ahmed Sheikh', 'shopowner1@uiu-canteen.local', '+8801722222222', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 2, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Shop Owner 2 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Fatima Khan', 'shopowner2@uiu-canteen.local', '+8801733333333', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 2, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Customer 1 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('John Doe', 'customer1@uiu-canteen.local', '+8801744444444', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 1, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Customer 2 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Maria Gonzalez', 'customer2@uiu-canteen.local', '+8801755555555', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 1, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Delivery Person 1 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Rajib Hasan', 'delivery1@uiu-canteen.local', '+8801766666666', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 4, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Delivery Person 2 (password: password123)
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role_id`, `profile_image`, `status`) VALUES
('Karim Ali', 'delivery2@uiu-canteen.local', '+8801777777777', '$2y$10$gAr9taAKTIu68WWG3ObvLeYqaAJ01B/cARsGzvYyLlcXLlSq5wJn2', 4, NULL, 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- =====================================================
-- INSERT SHOPS
-- Only UIU campus food vendors requested by the project owner
-- =====================================================
INSERT INTO `shops` (`owner_id`, `name`, `description`, `category_id`, `address`, `phone`, `logo_image`, `opening_time`, `closing_time`, `status`) VALUES
(2, 'Olympia Cafe', 'Popular UIU campus cafe serving breakfast, lunch, fast food, desserts, and drinks.', NULL, 'United International University, Dhaka', '+8801700000001', NULL, '08:00:00', '22:00:00', 'active'),
(3, 'Cafe Neptune', 'Convenient UIU campus food point for breakfast, snacks, and lunch favorites.', NULL, 'United International University, Dhaka', '+8801700000002', NULL, '08:00:00', '21:30:00', 'active'),
(2, 'KHAN''S KITCHEN', 'Campus favorite kitchen with hearty breakfast, lunch, fast food, desserts, and beverages.', NULL, 'United International University, Dhaka', '+8801700000003', NULL, '08:00:00', '22:30:00', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- INSERT FOODS
-- UIU campus menu only - no other shops
-- =====================================================
INSERT INTO `foods` (`shop_id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `preparation_time`) VALUES
-- Olympia Cafe
(1, NULL, 'Dal', 'Simple and hearty lentil dish for breakfast.', 45.00, NULL, 1, 10),
(1, NULL, 'Dal + Vegetables', 'Dal with seasonal vegetables and mild spices.', 60.00, NULL, 1, 12),
(1, NULL, 'Paratha', 'Freshly cooked layered flatbread.', 30.00, NULL, 1, 8),
(1, NULL, 'Plain Rice', 'Steamed plain rice for a filling lunch.', 40.00, NULL, 1, 8),
(1, NULL, 'Polao', 'Rice dish cooked with aromatic spices and rich flavor.', 90.00, NULL, 1, 15),
(1, NULL, 'Khichuri', 'Comforting rice and lentil mix.', 80.00, NULL, 1, 15),
(1, NULL, 'Chicken Tehari', 'Spiced chicken and rice plate.', 120.00, NULL, 1, 18),
(1, NULL, 'Chicken Vuna', 'Dry chicken preparation with savory spices.', 130.00, NULL, 1, 18),
(1, NULL, 'Chicken Roast', 'Roasted chicken served with rice or sides.', 140.00, NULL, 1, 20),
(1, NULL, 'Egg Korma', 'Egg curry prepared in a mild korma gravy.', 90.00, NULL, 1, 12),
(1, NULL, 'Egg Curry', 'Classic egg curry with curry sauce.', 80.00, NULL, 1, 12),
(1, NULL, 'Fish', 'Freshly prepared fish with local spices.', 150.00, NULL, 1, 18),
(1, NULL, 'Plain Dal', 'Classic dal bowl for a lighter meal.', 50.00, NULL, 1, 10),
(1, NULL, 'Vorta', 'Healthy vegetable mash with mustard and chili.', 40.00, NULL, 1, 8),
(1, 3, 'Pizza', 'Cheesy pizza slice for a quick fast-food bite.', 180.00, NULL, 1, 15),
(1, 1, 'Chicken Cheese Burger', 'Burger with grilled chicken and cheese.', 170.00, NULL, 1, 12),
(1, 3, 'Noodles', 'Hot and savory noodles.', 120.00, NULL, 1, 12),
(1, 1, 'Hot Dog', 'Classic hot dog served hot and fresh.', 100.00, NULL, 1, 10),
(1, 4, 'Pudding', 'Soft and creamy pudding dessert.', 60.00, NULL, 1, 5),
(1, 4, 'Plain Cake', 'Simple cake slice.', 55.00, NULL, 1, 5),
(1, 5, 'Lassy', 'Chilled yogurt drink.', 45.00, NULL, 1, 5),
(1, 5, 'Fresh Juice', 'Freshly blended seasonal fruit juice.', 70.00, NULL, 1, 5),
(1, 5, 'Soft Drink', 'Cold soft drink.', 35.00, NULL, 1, 2),
(1, 5, 'Tea', 'Hot tea for refreshment.', 25.00, NULL, 1, 3),
(1, 5, 'Coffee', 'Fresh brewed coffee.', 40.00, NULL, 1, 4),
(1, 5, 'Ice Cream', 'Chilled dessert cup.', 65.00, NULL, 1, 4),

-- Cafe Neptune
(2, NULL, 'Paratha', 'Fresh breakfast paratha.', 30.00, NULL, 1, 8),
(2, NULL, 'Dal', 'Breakfast dal serving.', 45.00, NULL, 1, 10),
(2, NULL, 'Mixed Vegetable', 'Seasonal mixed vegetables with spices.', 55.00, NULL, 1, 10),
(2, NULL, 'Egg Omelet', 'Classic omelet with eggs and vegetables.', 70.00, NULL, 1, 10),
(2, NULL, 'Singara', 'Crispy pastry snack.', 25.00, NULL, 1, 5),
(2, NULL, 'Jilapi', 'Sweet traditional syrupy snack.', 30.00, NULL, 1, 5),
(2, NULL, 'Puri', 'Fried flatbread snack.', 30.00, NULL, 1, 5),
(2, NULL, 'Pastry', 'Freshly baked pastry.', 35.00, NULL, 1, 5),
(2, NULL, 'Chola', 'Spiced chickpea preparation.', 40.00, NULL, 1, 8),
(2, NULL, 'Chicken Tehari', 'Rice and chicken lunch special.', 120.00, NULL, 1, 18),
(2, NULL, 'Khichuri', 'Comfort bowl of khichuri.', 80.00, NULL, 1, 15),
(2, NULL, 'Plain Rice', 'Steamed plain rice served with lunch.', 40.00, NULL, 1, 8),
(2, NULL, 'Chicken Curry', 'Hearty chicken curry with gravy.', 130.00, NULL, 1, 18),
(2, NULL, 'Boiled Egg', 'Simple boiled egg option.', 25.00, NULL, 1, 3),
(2, NULL, 'Mashed Potatoes', 'Creamy mashed potatoes.', 50.00, NULL, 1, 8),
(2, NULL, 'Thin Dal', 'Light dal for lunch.', 45.00, NULL, 1, 10),

-- KHAN'S KITCHEN
(3, NULL, 'Dal', 'Classic lentil dish.', 45.00, NULL, 1, 10),
(3, NULL, 'Dal + Vegetables', 'Dal prepared with vegetables.', 60.00, NULL, 1, 12),
(3, NULL, 'Paratha', 'Traditional flatbread.', 30.00, NULL, 1, 8),
(3, NULL, 'Plain Rice', 'Plain steamed rice.', 40.00, NULL, 1, 8),
(3, NULL, 'Polao', 'Aromatic rice meal.', 90.00, NULL, 1, 15),
(3, NULL, 'Khichuri', 'Rice-lentil combination meal.', 80.00, NULL, 1, 15),
(3, NULL, 'Chicken Tehari', 'Chicken and rice combo.', 120.00, NULL, 1, 18),
(3, NULL, 'Chicken Vuna', 'Spicy chicken dry dish.', 130.00, NULL, 1, 18),
(3, NULL, 'Chicken Roast', 'Roast chicken platter.', 140.00, NULL, 1, 20),
(3, NULL, 'Egg Korma', 'Egg in rich korma gravy.', 90.00, NULL, 1, 12),
(3, NULL, 'Egg Curry', 'Classic egg curry.', 80.00, NULL, 1, 12),
(3, NULL, 'Fish', 'Fresh local fish preparation.', 150.00, NULL, 1, 18),
(3, NULL, 'Plain Dal', 'Simple dal bowl.', 50.00, NULL, 1, 10),
(3, NULL, 'Vorta', 'Spiced mashed vegetables.', 40.00, NULL, 1, 8),
(3, 3, 'Pizza', 'Cheesy pizza slice.', 180.00, NULL, 1, 15),
(3, 1, 'Chicken Cheese Burger', 'Chicken burger with cheese.', 170.00, NULL, 1, 12),
(3, 3, 'Noodles', 'Savory noodles.', 120.00, NULL, 1, 12),
(3, 1, 'Hot Dog', 'Classic hot dog.', 100.00, NULL, 1, 10),
(3, 4, 'Pudding', 'Creamy dessert bowl.', 60.00, NULL, 1, 5),
(3, 4, 'Plain Cake', 'Serving of plain cake.', 55.00, NULL, 1, 5),
(3, 5, 'Lassy', 'Cooling yogurt drink.', 45.00, NULL, 1, 5),
(3, 5, 'Fresh Juice', 'Fresh fruit juice.', 70.00, NULL, 1, 5),
(3, 5, 'Soft Drink', 'Chilled soft drink.', 35.00, NULL, 1, 2),
(3, 5, 'Tea', 'Hot tea.', 25.00, NULL, 1, 3),
(3, 5, 'Coffee', 'Strong brewed coffee.', 40.00, NULL, 1, 4),
(3, 5, 'Ice Cream', 'Frozen dessert cup.', 65.00, NULL, 1, 4)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- INSERT SAMPLE CART
-- Customer ID: 4 (John Doe)
-- =====================================================
INSERT INTO `carts` (`customer_id`) VALUES (4)
ON DUPLICATE KEY UPDATE `customer_id` = VALUES(`customer_id`);

-- =====================================================
-- INSERT SAMPLE CART ITEMS
-- =====================================================
INSERT INTO `cart_items` (`cart_id`, `food_id`, `shop_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 250.00),
(1, 4, 1, 1, 60.00),
(1, 5, 2, 2, 150.00)
ON DUPLICATE KEY UPDATE `quantity` = VALUES(`quantity`);

-- =====================================================
-- INSERT SAMPLE ORDERS
-- Customer ID: 5 (Maria Gonzalez)
-- =====================================================
INSERT INTO `orders` (`customer_id`, `delivery_address`, `total_amount`, `status`, `payment_method`) VALUES
(5, '789 Student Lane, Dhaka', 410.00, 'delivered', 'cash'),
(5, '789 Student Lane, Dhaka', 580.00, 'picked_up', 'card'),
(4, '321 University Plaza, Dhaka', 370.00, 'pending', 'card')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- =====================================================
-- INSERT SAMPLE ORDER ITEMS
-- =====================================================
INSERT INTO `order_items` (`order_id`, `food_id`, `shop_id`, `name`, `quantity`, `price`) VALUES
(1, 1, 1, 'Chicken Biryani', 1, 250.00),
(1, 4, 1, 'Mango Lassi', 1, 60.00),
(1, 5, 2, 'Chicken Burger', 2, 150.00),
(2, 2, 1, 'Mutton Biryani', 1, 300.00),
(2, 7, 2, 'Fries', 1, 80.00),
(2, 9, 2, 'Chocolate Cake', 1, 100.00),
(2, 10, 2, 'Coca Cola', 1, 35.00),
(3, 6, 2, 'Beef Burger', 1, 180.00),
(3, 8, 2, 'Shawarma', 1, 120.00),
(3, 7, 2, 'Fries', 1, 80.00)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- INSERT SAMPLE DELIVERIES
-- Delivery Person IDs: 6 (Rajib Hasan), 7 (Karim Ali)
-- =====================================================
INSERT INTO `deliveries` (`order_id`, `delivery_person_id`, `status`, `accepted_time`, `pickup_time`, `delivered_time`, `earning`) VALUES
(1, 6, 'delivered', '2026-08-30 12:30:00', '2026-08-30 12:45:00', '2026-08-30 13:20:00', 50.00),
(2, 7, 'picked_up', '2026-08-30 13:00:00', '2026-08-30 13:15:00', NULL, NULL),
(3, 6, 'pending', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- =====================================================
-- INSERT SAMPLE REPORTS
-- =====================================================
INSERT INTO `reports` (`customer_id`, `order_id`, `shop_id`, `category`, `description`, `evidence_image`, `status`, `admin_response`) VALUES
(5, 1, 1, 'Quality', 'The biryani was cold when delivered', NULL, 'resolved', 'Apologize for inconvenience. We have addressed this with the shop owner.')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- =====================================================
-- INSERT SAMPLE ADMIN ACTIONS
-- Admin ID: 1 (Admin User)
-- =====================================================
INSERT INTO `admin_actions` (`admin_id`, `shop_id`, `action_type`, `fine_amount`, `admin_note`, `action_date`) VALUES
(1, 1, 'warning', NULL, 'Multiple customer complaints about cold food. Warned shop owner.', '2026-08-29 10:00:00')
ON DUPLICATE KEY UPDATE `admin_note` = VALUES(`admin_note`);

-- =====================================================
-- CREATE PERFORMANCE INDEXES
-- =====================================================
CREATE INDEX idx_users_email_role ON users(email, role_id);
CREATE INDEX idx_shops_owner_status ON shops(owner_id, status);
CREATE INDEX idx_foods_shop_available ON foods(shop_id, is_available);
CREATE INDEX idx_orders_customer_status ON orders(customer_id, status);
CREATE INDEX idx_order_items_shop_order ON order_items(shop_id, order_id);
CREATE INDEX idx_deliveries_person_status ON deliveries(delivery_person_id, status);
CREATE INDEX idx_reports_shop_status ON reports(shop_id, status);
CREATE INDEX idx_admin_actions_shop_date ON admin_actions(shop_id, action_date);
