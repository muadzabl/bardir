-- ==========================================================
-- BARDIR - Barbershop Operation & Payroll System
-- Database Schema & Initial Data
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `barberos_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `barberos_db`;

-- Drop tables in reverse order of foreign keys
DROP TABLE IF EXISTS `transaction_details`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `barbers`;
DROP TABLE IF EXISTS `users`;

-- 1. USERS TABLE (Owner, Cashier / Admin)
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('owner', 'cashier') NOT NULL DEFAULT 'cashier',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BARBERS TABLE (Kapster / Hair Stylists)
CREATE TABLE `barbers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SERVICES TABLE (Daftar Layanan, Harga & Komisi Default)
CREATE TABLE `services` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `default_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TRANSACTIONS TABLE (Header Transaksi Harian & Diskon)
CREATE TABLE `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('cash', 'qris', 'transfer', 'debit') NOT NULL DEFAULT 'cash',
    `notes` VARCHAR(255) DEFAULT NULL,
    `cashier_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_transactions_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TRANSACTION DETAILS TABLE (Detail Layanan & Komisi Kapster per Item)
CREATE TABLE `transaction_details` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT UNSIGNED NOT NULL,
    `service_id` INT UNSIGNED NOT NULL,
    `barber_id` INT UNSIGNED NOT NULL,
    `service_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `barber_commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_details_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_details_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_details_barber` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATA AWAL (DEFAULT ACCOUNTS & SERVICES)
-- Password default: 'password123'
-- ==========================================================

-- Akun Owner & Kasir
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Agus Pratama (Owner)', 'owner@barberos.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner'),
(2, 'Budi Santoso (Kasir)', 'cashier@barberos.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier');

-- Daftar Kapster Awal
INSERT INTO `barbers` (`id`, `name`, `phone`, `status`) VALUES
(1, 'Rian Hidayat', '081234567891', 'active'),
(2, 'Doni Setiawan', '081234567892', 'active'),
(3, 'Eko Prasetyo', '081234567893', 'active');

-- Daftar Layanan & Komisi Awal
INSERT INTO `services` (`id`, `name`, `price`, `default_commission`, `is_active`) VALUES
(1, 'Gentleman Haircut + Wash', 50000.00, 20000.00, 1),
(2, 'Premium Haircut + Massage + Hot Towel', 75000.00, 30000.00, 1),
(3, 'Kids Haircut', 40000.00, 15000.00, 1),
(4, 'Beard Trim & Shave', 25000.00, 10000.00, 1),
(5, 'Hair Color / Basic Black', 60000.00, 25000.00, 1);

-- Sample Transaksi Awal
INSERT INTO `transactions` (`id`, `transaction_code`, `subtotal`, `discount_amount`, `total_amount`, `payment_method`, `cashier_id`, `created_at`) VALUES
(1, 'TRX-20261005-001', 50000.00, 0.00, 50000.00, 'cash', 2, NOW() - INTERVAL 4 HOUR),
(2, 'TRX-20261005-002', 105000.00, 5000.00, 100000.00, 'qris', 2, NOW() - INTERVAL 2 HOUR);

INSERT INTO `transaction_details` (`transaction_id`, `service_id`, `barber_id`, `service_price`, `barber_commission_amount`) VALUES
(1, 1, 1, 50000.00, 20000.00),
(2, 2, 2, 75000.00, 30000.00),
(2, 4, 2, 30000.00, 10000.00);
