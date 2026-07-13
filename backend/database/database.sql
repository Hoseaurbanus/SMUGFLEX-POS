-- ============================================================
-- SmugFlex POS - Complete Database Schema
-- Version: 1.0.0
-- MySQL 8.0+ / MariaDB 10.6+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `smugflex_pos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smugflex_pos`;

-- ============================================================
-- ROLES & PERMISSIONS
-- ============================================================

CREATE TABLE `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_slug` (`slug`)
) ENGINE=InnoDB;

CREATE TABLE `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_permissions_slug` (`slug`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB;

CREATE TABLE `role_permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- BRANCHES & WAREHOUSES
-- ============================================================

CREATE TABLE `branches` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL DEFAULT 'Nigeria',
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_branches_code` (`code`)
) ENGINE=InnoDB;

CREATE TABLE `warehouses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `branch_id` INT UNSIGNED NULL,
    `address` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_warehouses_code` (`code`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- USERS
-- ============================================================

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `avatar` VARCHAR(255) NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NULL,
    `warehouse_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `remember_token` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE SET NULL,
    KEY `idx_users_active` (`is_active`),
    KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES (Nested Set)
-- ============================================================

CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categories_slug` (`slug`),
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- BRANDS
-- ============================================================

CREATE TABLE `brands` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_brands_slug` (`slug`)
) ENGINE=InnoDB;

-- ============================================================
-- UNITS
-- ============================================================

CREATE TABLE `units` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `short_name` VARCHAR(20) NOT NULL,
    `base_unit_id` INT UNSIGNED NULL,
    `conversion_factor` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_units_short` (`short_name`),
    FOREIGN KEY (`base_unit_id`) REFERENCES `units`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCTS
-- ============================================================

CREATE TABLE `products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL,
    `sku` VARCHAR(50) NOT NULL,
    `barcode` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `category_id` INT UNSIGNED NULL,
    `brand_id` INT UNSIGNED NULL,
    `unit_id` INT UNSIGNED NULL,
    `buying_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `wholesale_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `minimum_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `minimum_stock` INT NOT NULL DEFAULT 0,
    `has_variants` TINYINT(1) NOT NULL DEFAULT 0,
    `has_serial` TINYINT(1) NOT NULL DEFAULT 0,
    `has_expiry` TINYINT(1) NOT NULL DEFAULT 0,
    `image` VARCHAR(255) NULL,
    `status` ENUM('active','inactive','discontinued') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_products_sku` (`sku`),
    UNIQUE KEY `uk_products_slug` (`slug`),
    KEY `idx_products_barcode` (`barcode`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_brand` (`brand_id`),
    KEY `idx_products_status` (`status`),
    KEY `idx_products_deleted` (`deleted_at`),
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCT VARIANTS
-- ============================================================

CREATE TABLE `product_variants` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `sku` VARCHAR(50) NOT NULL,
    `barcode` VARCHAR(50) NULL,
    `buying_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `wholesale_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `image` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_variants_sku` (`sku`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCT STOCK
-- ============================================================

CREATE TABLE `product_stocks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `reserved_quantity` INT NOT NULL DEFAULT 0,
    `expiry_date` DATE NULL,
    `batch_number` VARCHAR(50) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stock_product_warehouse` (`product_id`, `variant_id`, `warehouse_id`),
    KEY `idx_stock_warehouse` (`warehouse_id`),
    KEY `idx_stock_expiry` (`expiry_date`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- STOCK MOVEMENTS
-- ============================================================

CREATE TABLE `stock_movements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `type` ENUM('in','out','transfer','adjustment','sale','purchase','return') NOT NULL,
    `quantity` INT NOT NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_movement_product` (`product_id`),
    KEY `idx_movement_warehouse` (`warehouse_id`),
    KEY `idx_movement_type` (`type`),
    KEY `idx_movement_reference` (`reference_type`, `reference_id`),
    KEY `idx_movement_created` (`created_at`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- CUSTOMERS
-- ============================================================

CREATE TABLE `customers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL DEFAULT 'Nigeria',
    `tax_number` VARCHAR(50) NULL,
    `credit_limit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `outstanding_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `reward_points` INT NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_customers_email` (`email`),
    KEY `idx_customers_phone` (`phone`),
    KEY `idx_customers_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- CUSTOMER WALLET
-- ============================================================

CREATE TABLE `customer_wallets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED NOT NULL,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_wallet_customer` (`customer_id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `customer_wallet_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wallet_id` INT UNSIGNED NOT NULL,
    `type` ENUM('credit','debit') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `description` VARCHAR(255) NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`wallet_id`) REFERENCES `customer_wallets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- SUPPLIERS
-- ============================================================

CREATE TABLE `suppliers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100) NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL DEFAULT 'Nigeria',
    `tax_number` VARCHAR(50) NULL,
    `bank_name` VARCHAR(100) NULL,
    `bank_account_number` VARCHAR(50) NULL,
    `bank_account_name` VARCHAR(100) NULL,
    `outstanding_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_suppliers_deleted` (`deleted_at`)
) ENGINE=InnoDB;

-- ============================================================
-- PURCHASES
-- ============================================================

CREATE TABLE `purchases` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `supplier_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending','received','partial','cancelled') NOT NULL DEFAULT 'pending',
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `shipping_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `due_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    `order_date` DATE NOT NULL,
    `expected_date` DATE NULL,
    `received_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_purchases_ref` (`reference_number`),
    KEY `idx_purchases_supplier` (`supplier_id`),
    KEY `idx_purchases_status` (`status`),
    KEY `idx_purchases_date` (`order_date`),
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `purchase_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `quantity` INT NOT NULL,
    `received_quantity` INT NOT NULL DEFAULT 0,
    `unit_cost` DECIMAL(12,2) NOT NULL,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `purchase_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash','transfer','card','cheque') NOT NULL DEFAULT 'cash',
    `reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `payment_date` DATE NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- SALES
-- ============================================================

CREATE TABLE `sales` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `customer_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `shipping_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `due_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('paid','partial','unpaid','refunded') NOT NULL DEFAULT 'paid',
    `sale_status` ENUM('completed','held','voided','returned') NOT NULL DEFAULT 'completed',
    `payment_method` ENUM('cash','card','transfer','wallet','mixed') NOT NULL DEFAULT 'cash',
    `coupon_code` VARCHAR(50) NULL,
    `discount_type` ENUM('percentage','fixed') NULL,
    `notes` TEXT NULL,
    `sale_date` DATE NOT NULL,
    `sale_time` TIME NOT NULL,
    `hold_reference` VARCHAR(50) NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sales_invoice` (`invoice_number`),
    KEY `idx_sales_customer` (`customer_id`),
    KEY `idx_sales_user` (`user_id`),
    KEY `idx_sales_date` (`sale_date`),
    KEY `idx_sales_status` (`sale_status`),
    KEY `idx_sales_payment` (`payment_status`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `sale_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
    FOREIGN KEY (`variant_id`) REFERENCES `product_variants`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `sale_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash','card','transfer','wallet','gift_card') NOT NULL,
    `reference` VARCHAR(100) NULL,
    `card_last_four` VARCHAR(4) NULL,
    `notes` TEXT NULL,
    `payment_date` DATE NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- SALES RETURNS & REFUNDS
-- ============================================================

CREATE TABLE `sale_returns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `return_number` VARCHAR(50) NOT NULL,
    `sale_id` INT UNSIGNED NOT NULL,
    `customer_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `refund_method` ENUM('cash','transfer','wallet','credit','exchange') NOT NULL DEFAULT 'cash',
    `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `reason` TEXT NULL,
    `status` ENUM('pending','approved','completed','rejected') NOT NULL DEFAULT 'pending',
    `return_date` DATE NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_returns_number` (`return_number`),
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`)
) ENGINE=InnoDB;

CREATE TABLE `return_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `return_id` INT UNSIGNED NOT NULL,
    `sale_item_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `reason` TEXT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`return_id`) REFERENCES `sale_returns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items`(`id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- EXPENSES
-- ============================================================

CREATE TABLE `expense_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `expenses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) NOT NULL,
    `expense_category_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash','transfer','card','cheque') NOT NULL DEFAULT 'cash',
    `expense_date` DATE NOT NULL,
    `description` TEXT NULL,
    `receipt_file` VARCHAR(255) NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
    `recurring_frequency` ENUM('daily','weekly','monthly','yearly') NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_expenses_ref` (`reference_number`),
    FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    KEY `idx_expenses_date` (`expense_date`)
) ENGINE=InnoDB;

-- ============================================================
-- COUPONS & GIFT CARDS
-- ============================================================

CREATE TABLE `coupons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    `value` DECIMAL(12,2) NOT NULL,
    `minimum_purchase` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `maximum_discount` DECIMAL(12,2) NULL,
    `usage_limit` INT NULL,
    `used_count` INT NOT NULL DEFAULT 0,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coupons_code` (`code`)
) ENGINE=InnoDB;

CREATE TABLE `gift_cards` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `balance` DECIMAL(12,2) NOT NULL,
    `initial_amount` DECIMAL(12,2) NOT NULL,
    `customer_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `expiry_date` DATE NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_gift_cards_code` (`code`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- LOYALTY / REWARD POINTS
-- ============================================================

CREATE TABLE `reward_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED NOT NULL,
    `type` ENUM('earned','redeemed','adjusted') NOT NULL,
    `points` INT NOT NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SHIFTS & CASH DRAWERS
-- ============================================================

CREATE TABLE `shifts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `opening_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `closing_balance` DECIMAL(12,2) NULL,
    `expected_balance` DECIMAL(12,2) NULL,
    `difference` DECIMAL(12,2) NULL,
    `total_sales` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_returns` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_expenses` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
    `opened_at` DATETIME NOT NULL,
    `closed_at` DATETIME NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- ATTENDANCE & PAYROLL
-- ============================================================

CREATE TABLE `attendance` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `check_in` DATETIME NULL,
    `check_out` DATETIME NULL,
    `hours_worked` DECIMAL(5,2) NULL,
    `overtime_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('present','absent','late','half_day','on_leave') NOT NULL DEFAULT 'present',
    `notes` TEXT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_attendance_user_date` (`user_id`, `date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `payroll` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `overtime_pay` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('cash','transfer') NOT NULL DEFAULT 'transfer',
    `status` ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    `paid_date` DATE NULL,
    `notes` TEXT NULL,
    `user_id_creator` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`user_id_creator`) REFERENCES `users`(`id`),
    KEY `idx_payroll_period` (`period_start`, `period_end`)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

CREATE TABLE `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    `module` VARCHAR(50) NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user` (`user_id`),
    KEY `idx_notifications_read` (`is_read`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY LOGS
-- ============================================================

CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_activity_user` (`user_id`),
    KEY `idx_activity_module` (`module`),
    KEY `idx_activity_action` (`action`),
    KEY `idx_activity_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SETTINGS
-- ============================================================

CREATE TABLE `company` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL DEFAULT 'SmugFlex Store',
    `slug` VARCHAR(200) NULL,
    `email` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL DEFAULT 'Nigeria',
    `logo` VARCHAR(255) NULL,
    `tax_number` VARCHAR(50) NULL,
    `registration_number` VARCHAR(50) NULL,
    `website` VARCHAR(200) NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'NGN',
    `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '₦',
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `receipt_header` TEXT NULL,
    `receipt_footer` TEXT NULL,
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Africa/Lagos',
    `date_format` VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
    `time_format` VARCHAR(20) NOT NULL DEFAULT '12',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_name` VARCHAR(50) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `type` ENUM('text','number','boolean','json','file') NOT NULL DEFAULT 'text',
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`),
    KEY `idx_settings_group` (`group_name`)
) ENGINE=InnoDB;

-- ============================================================
-- BACKUPS LOG
-- ============================================================

CREATE TABLE `backups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `filename` VARCHAR(255) NOT NULL,
    `size` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('completed','failed') NOT NULL DEFAULT 'completed',
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

CREATE TRIGGER `trg_stock_after_insert` AFTER INSERT ON `stock_movements`
FOR EACH ROW
BEGIN
    INSERT INTO `product_stocks` (`product_id`, `variant_id`, `warehouse_id`, `quantity`, `created_at`)
    VALUES (NEW.product_id, NEW.variant_id, NEW.warehouse_id, IF(NEW.type IN ('in','purchase','return'), NEW.quantity, -NEW.quantity), NOW())
    ON DUPLICATE KEY UPDATE
        `quantity` = `quantity` + IF(NEW.type IN ('in','purchase','return'), NEW.quantity, -NEW.quantity),
        `updated_at` = NOW();
END//

DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
('Super Admin', 'super-admin', 'Full system access', 1, NOW(), NOW()),
('Admin', 'admin', 'Administrative access', 1, NOW(), NOW()),
('Manager', 'manager', 'Store management access', 0, NOW(), NOW()),
('Cashier', 'cashier', 'POS and sales access', 0, NOW(), NOW()),
('Accountant', 'accountant', 'Financial reports access', 0, NOW(), NOW()),
('Inventory Officer', 'inventory-officer', 'Stock management access', 0, NOW(), NOW()),
('Store Keeper', 'store-keeper', 'Warehouse operations access', 0, NOW(), NOW()),
('Auditor', 'auditor', 'Read-only audit access', 0, NOW(), NOW());

INSERT INTO `permissions` (`name`, `slug`, `module`, `created_at`) VALUES
-- Dashboard
('View Dashboard', 'dashboard.view', 'dashboard', NOW()),
-- Products
('View Products', 'products.view', 'products', NOW()),
('Create Products', 'products.create', 'products', NOW()),
('Edit Products', 'products.edit', 'products', NOW()),
('Delete Products', 'products.delete', 'products', NOW()),
('Import Products', 'products.import', 'products', NOW()),
-- Categories
('View Categories', 'categories.view', 'categories', NOW()),
('Manage Categories', 'categories.manage', 'categories', NOW()),
-- Brands
('View Brands', 'brands.view', 'brands', NOW()),
('Manage Brands', 'brands.manage', 'brands', NOW()),
-- Units
('View Units', 'units.view', 'units', NOW()),
('Manage Units', 'units.manage', 'units', NOW()),
-- POS
('Access POS', 'pos.access', 'pos', NOW()),
('Hold Sales', 'pos.hold', 'pos', NOW()),
('Apply Discounts', 'pos.discount', 'pos', NOW()),
('Process Returns', 'pos.return', 'pos', NOW()),
-- Sales
('View Sales', 'sales.view', 'sales', NOW()),
('Create Sales', 'sales.create', 'sales', NOW()),
('Void Sales', 'sales.void', 'sales', NOW()),
('Export Sales', 'sales.export', 'sales', NOW()),
-- Purchases
('View Purchases', 'purchases.view', 'purchases', NOW()),
('Create Purchases', 'purchases.create', 'purchases', NOW()),
('Receive Purchases', 'purchases.receive', 'purchases', NOW()),
('Return Purchases', 'purchases.return', 'purchases', NOW()),
-- Customers
('View Customers', 'customers.view', 'customers', NOW()),
('Create Customers', 'customers.create', 'customers', NOW()),
('Edit Customers', 'customers.edit', 'customers', NOW()),
('Delete Customers', 'customers.delete', 'customers', NOW()),
('Manage Customer Wallet', 'customers.wallet', 'customers', NOW()),
-- Suppliers
('View Suppliers', 'suppliers.view', 'suppliers', NOW()),
('Create Suppliers', 'suppliers.create', 'suppliers', NOW()),
('Edit Suppliers', 'suppliers.edit', 'suppliers', NOW()),
('Delete Suppliers', 'suppliers.delete', 'suppliers', NOW()),
-- Inventory
('View Inventory', 'inventory.view', 'inventory', NOW()),
('Adjust Stock', 'inventory.adjust', 'inventory', NOW()),
('Transfer Stock', 'inventory.transfer', 'inventory', NOW()),
-- Expenses
('View Expenses', 'expenses.view', 'expenses', NOW()),
('Create Expenses', 'expenses.create', 'expenses', NOW()),
('Manage Expense Categories', 'expenses.categories', 'expenses', NOW()),
-- Reports
('View Reports', 'reports.view', 'reports', NOW()),
('Export Reports', 'reports.export', 'reports', NOW()),
-- Users
('View Users', 'users.view', 'users', NOW()),
('Create Users', 'users.create', 'users', NOW()),
('Edit Users', 'users.edit', 'users', NOW()),
('Delete Users', 'users.delete', 'users', NOW()),
('Manage Roles', 'users.roles', 'users', NOW()),
-- Settings
('View Settings', 'settings.view', 'settings', NOW()),
('Edit Settings', 'settings.edit', 'settings', NOW()),
('Backup & Restore', 'settings.backup', 'settings', NOW()),
-- Warehouses & Branches
('View Warehouses', 'warehouses.view', 'warehouses', NOW()),
('Manage Warehouses', 'warehouses.manage', 'warehouses', NOW()),
('View Branches', 'branches.view', 'branches', NOW()),
('Manage Branches', 'branches.manage', 'branches', NOW()),
-- Notifications
('View Notifications', 'notifications.view', 'notifications', NOW()),
('Manage Notifications', 'notifications.manage', 'notifications', NOW()),
-- Activity Log
('View Activity Log', 'activity.view', 'activity', NOW()),
-- Employees
('View Employees', 'employees.view', 'employees', NOW()),
('Manage Attendance', 'employees.attendance', 'employees', NOW()),
('Manage Payroll', 'employees.payroll', 'employees', NOW());

-- Assign all permissions to Super Admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 1, id, NOW() FROM `permissions`;

-- Assign broad permissions to Admin (all except backup & payroll)
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 2, id, NOW() FROM `permissions` WHERE slug NOT IN ('settings.backup', 'employees.payroll', 'users.roles');

-- Assign manager permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 3, id, NOW() FROM `permissions` WHERE slug IN (
    'dashboard.view', 'products.view', 'products.create', 'products.edit',
    'categories.view', 'categories.manage', 'brands.view', 'brands.manage',
    'units.view', 'units.manage', 'pos.access', 'pos.hold', 'pos.discount', 'pos.return',
    'sales.view', 'sales.create', 'purchases.view', 'purchases.create', 'purchases.receive',
    'customers.view', 'customers.create', 'customers.edit', 'customers.wallet',
    'suppliers.view', 'suppliers.create', 'suppliers.edit',
    'inventory.view', 'inventory.adjust', 'inventory.transfer',
    'expenses.view', 'expenses.create',
    'reports.view', 'reports.export',
    'employees.view', 'employees.attendance'
);

-- Assign cashier permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 4, id, NOW() FROM `permissions` WHERE slug IN (
    'dashboard.view', 'pos.access', 'pos.hold', 'pos.discount', 'pos.return',
    'sales.view', 'sales.create',
    'customers.view', 'customers.create',
    'products.view', 'inventory.view', 'notifications.view'
);

-- Insert default branch and warehouse
INSERT INTO `branches` (`name`, `code`, `address`, `city`, `country`, `created_at`, `updated_at`) VALUES
('Main Branch', 'MB001', '123 SmugFlex Avenue', 'Lagos', 'Nigeria', NOW(), NOW()),
('Branch 2', 'BR002', '456 Commerce Street', 'Abuja', 'Nigeria', NOW(), NOW());

INSERT INTO `warehouses` (`name`, `code`, `branch_id`, `address`, `created_at`, `updated_at`) VALUES
('Main Warehouse', 'WH001', 1, '123 SmugFlex Avenue, Lagos', NOW(), NOW()),
('Branch 2 Warehouse', 'WH002', 2, '456 Commerce Street, Abuja', NOW(), NOW());

-- Insert Super Admin user (password: password)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role_id`, `branch_id`, `warehouse_id`, `is_active`, `created_at`, `updated_at`) VALUES
('Super', 'Admin', 'admin@smugflex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, 1, NOW(), NOW()),
('John', 'Cashier', 'cashier@smugflex.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1, 1, 1, NOW(), NOW());

-- Insert default company
INSERT INTO `company` (`name`, `email`, `phone`, `address`, `city`, `country`, `currency`, `currency_symbol`, `tax_rate`, `timezone`, `created_at`, `updated_at`) VALUES
('SmugFlex Ventures', 'info@smugflex.com', '+234 800 123 4567', '123 SmugFlex Avenue', 'Lagos', 'Nigeria', 'NGN', '₦', 7.50, 'Africa/Lagos', NOW(), NOW());

-- Insert default expense categories
INSERT INTO `expense_categories` (`name`, `description`, `created_at`) VALUES
('Rent', 'Office and store rent', NOW()),
('Utilities', 'Electricity, water, internet', NOW()),
('Salaries', 'Employee salaries', NOW()),
('Transportation', 'Fuel, logistics, delivery', NOW()),
('Marketing', 'Advertising and promotions', NOW()),
('Maintenance', 'Equipment and facility maintenance', NOW()),
('Office Supplies', 'Stationery and office items', NOW()),
('Miscellaneous', 'Other expenses', NOW());

-- Insert default units
INSERT INTO `units` (`name`, `short_name`, `created_at`) VALUES
('Piece', 'pc', NOW()),
('Kilogram', 'kg', NOW()),
('Gram', 'g', NOW()),
('Liter', 'L', NOW()),
('Milliliter', 'mL', NOW()),
('Box', 'box', NOW()),
('Pack', 'pk', NOW()),
('Dozen', 'dz', NOW()),
('Carton', 'ctn', NOW()),
('Meter', 'm', NOW()),
('Pair', 'pr', NOW()),
('Set', 'set', NOW());

-- Insert sample categories
INSERT INTO `categories` (`name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
('Electronics', 'electronics', 'Electronic devices and accessories', NOW(), NOW()),
('Clothing', 'clothing', 'Apparel and fashion items', NOW(), NOW()),
('Groceries', 'groceries', 'Food and grocery items', NOW(), NOW()),
('Home & Kitchen', 'home-kitchen', 'Home appliances and kitchen items', NOW(), NOW()),
('Beauty & Health', 'beauty-health', 'Beauty products and health items', NOW(), NOW()),
('Sports & Outdoors', 'sports-outdoors', 'Sports equipment and outdoor items', NOW(), NOW()),
('Books & Stationery', 'books-stationery', 'Books, pens, and office supplies', NOW(), NOW()),
('Toys & Games', 'toys-games', 'Children toys and games', NOW(), NOW());

-- Insert sample brands
INSERT INTO `brands` (`name`, `slug`, `created_at`, `updated_at`) VALUES
('Samsung', 'samsung', NOW(), NOW()),
('Apple', 'apple', NOW(), NOW()),
('Nike', 'nike', NOW(), NOW()),
('Adidas', 'adidas', NOW(), NOW()),
('LG', 'lg', NOW(), NOW()),
('Sony', 'sony', NOW(), NOW()),
('Infinix', 'infinix', NOW(), NOW()),
('Tecno', 'tecno', NOW(), NOW());

-- Insert sample products
INSERT INTO `products` (`name`, `slug`, `sku`, `barcode`, `category_id`, `brand_id`, `unit_id`, `buying_price`, `selling_price`, `wholesale_price`, `tax_rate`, `minimum_stock`, `status`, `created_at`, `updated_at`) VALUES
('Samsung Galaxy A54', 'samsung-galaxy-a54', 'SAM-A54-001', '6901234567890', 1, 1, 1, 150000.00, 195000.00, 185000.00, 7.50, 5, 'active', NOW(), NOW()),
('iPhone 15 Pro', 'iphone-15-pro', 'APL-15P-001', '1942533978671', 1, 2, 1, 450000.00, 550000.00, 520000.00, 7.50, 3, 'active', NOW(), NOW()),
('Nike Air Max 270', 'nike-air-max-270', 'NIK-AM270-001', '1234567890123', 2, 3, 11, 35000.00, 55000.00, 50000.00, 7.50, 10, 'active', NOW(), NOW()),
('Infinix Note 30', 'infinix-note-30', 'INX-N30-001', '6901234567891', 1, 7, 1, 95000.00, 125000.00, 115000.00, 7.50, 8, 'active', NOW(), NOW()),
('LG Smart TV 55"', 'lg-smart-tv-55', 'LG-TV55-001', '8806084712345', 1, 5, 1, 320000.00, 420000.00, 400000.00, 7.50, 2, 'active', NOW(), NOW()),
('Tecno Spark 20', 'tecno-spark-20', 'TEC-S20-001', '6901234567892', 1, 8, 1, 65000.00, 85000.00, 80000.00, 7.50, 10, 'active', NOW(), NOW()),
('Sony WH-1000XM5', 'sony-wh-1000xm5', 'SNY-WH5-001', '4548736123456', 1, 6, 1, 120000.00, 165000.00, 155000.00, 7.50, 5, 'active', NOW(), NOW()),
('Adidas Ultraboost', 'adidas-ultraboost', 'ADI-UB-001', '4064041876543', 2, 4, 11, 40000.00, 65000.00, 60000.00, 7.50, 8, 'active', NOW(), NOW());

-- Initialize stock for products
INSERT INTO `product_stocks` (`product_id`, `warehouse_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 25, NOW(), NOW()),
(2, 1, 15, NOW(), NOW()),
(3, 1, 30, NOW(), NOW()),
(4, 1, 20, NOW(), NOW()),
(5, 1, 8, NOW(), NOW()),
(6, 1, 35, NOW(), NOW()),
(7, 1, 12, NOW(), NOW()),
(8, 1, 18, NOW(), NOW());

-- Insert sample customers
INSERT INTO `customers` (`first_name`, `last_name`, `email`, `phone`, `address`, `city`, `reward_points`, `created_at`, `updated_at`) VALUES
('Chinedu', 'Okafor', 'chinedu@email.com', '+234 801 234 5678', '15 Victoria Island', 'Lagos', 250, NOW(), NOW()),
('Amina', 'Abdullahi', 'amina@email.com', '+234 802 345 6789', '22 Wuse Zone 5', 'Abuja', 180, NOW(), NOW()),
('Emeka', 'Nwankwo', 'emeka@email.com', '+234 803 456 7890', '8 GRA Road', 'Port Harcourt', 420, NOW(), NOW()),
('Fatima', 'Bello', 'fatima@email.com', '+234 804 567 8901', '30 Kaduna Road', 'Kano', 95, NOW(), NOW()),
('Oluwaseun', 'Adeyemi', 'oluwaseun@email.com', '+234 805 678 9012', '7 Ibadan Crescent', 'Ibadan', 310, NOW(), NOW());

-- Initialize customer wallets
INSERT INTO `customer_wallets` (`customer_id`, `balance`, `created_at`, `updated_at`) VALUES
(1, 15000.00, NOW(), NOW()),
(2, 8500.00, NOW(), NOW()),
(3, 22000.00, NOW(), NOW()),
(4, 3000.00, NOW(), NOW()),
(5, 12000.00, NOW(), NOW());

-- Insert sample suppliers
INSERT INTO `suppliers` (`name`, `contact_person`, `email`, `phone`, `address`, `city`, `outstanding_balance`, `created_at`, `updated_at`) VALUES
('Tech Distributors Nigeria', 'Ibrahim Musa', 'ibrahim@techdist.ng', '+234 810 111 2222', '10 Computer Village', 'Lagos', 0.00, NOW(), NOW()),
('Fashion Hub Wholesale', 'Grace Eze', 'grace@fashionhub.ng', '+234 811 333 4444', '25 Balogun Market', 'Lagos', 0.00, NOW(), NOW()),
('Global Electronics Ltd', 'Mohammed Ali', 'mohammed@globalelec.ng', '+234 812 555 6666', '5 Industrial Layout', 'Abuja', 0.00, NOW(), NOW());

-- Insert default settings
INSERT INTO `settings` (`group_name`, `setting_key`, `setting_value`, `type`, `created_at`, `updated_at`) VALUES
('general', 'theme', 'dark', 'text', NOW(), NOW()),
('general', 'language', 'en', 'text', NOW(), NOW()),
('general', 'timezone', 'Africa/Lagos', 'text', NOW(), NOW()),
('receipt', 'paper_size', '80mm', 'text', NOW(), NOW()),
('receipt', 'show_logo', '1', 'boolean', NOW(), NOW()),
('receipt', 'show_barcode', '1', 'boolean', NOW(), NOW()),
('receipt', 'show_qrcode', '0', 'boolean', NOW(), NOW()),
('tax', 'enable_tax', '1', 'boolean', NOW(), NOW()),
('tax', 'default_tax_rate', '7.50', 'number', NOW(), NOW()),
('loyalty', 'enable_loyalty', '1', 'boolean', NOW(), NOW()),
('loyalty', 'points_per_naira', '0.01', 'number', NOW(), NOW()),
('loyalty', 'redemption_rate', '100', 'number', NOW(), NOW()),
('pos', 'enable_held_sales', '1', 'boolean', NOW(), NOW()),
('pos', 'enable_customer_display', '1', 'boolean', NOW(), NOW()),
('pos', 'default_warehouse', '1', 'number', NOW(), NOW()),
('backup', 'auto_backup', '0', 'boolean', NOW(), NOW()),
('backup', 'backup_frequency', 'daily', 'text', NOW(), NOW());

COMMIT;
