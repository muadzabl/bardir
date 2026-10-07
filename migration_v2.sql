-- ==========================================================
-- BARDIR - Migration: Stok Produk & Antrean
-- Jalankan script ini sekali di phpMyAdmin atau MySQL CLI
-- ==========================================================

USE `barberos_db`;

-- 6. STOCK PRODUCTS TABLE (Stok Produk / Bahan)
CREATE TABLE IF NOT EXISTS `stock_products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(80) NOT NULL DEFAULT 'Umum',
    `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
    `stock_qty` INT NOT NULL DEFAULT 0,
    `min_stock` INT NOT NULL DEFAULT 5 COMMENT 'Batas stok minimum (alert)',
    `buy_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga beli/modal',
    `sell_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga jual (jika ada)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. STOCK LOGS TABLE (Riwayat Perubahan Stok)
CREATE TABLE IF NOT EXISTS `stock_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `change_qty` INT NOT NULL COMMENT 'Positif = masuk, Negatif = keluar',
    `note` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_stock_logs_product` FOREIGN KEY (`product_id`) REFERENCES `stock_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. QUEUE TABLE (Antrean Pelanggan Harian)
CREATE TABLE IF NOT EXISTS `queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue_number` SMALLINT UNSIGNED NOT NULL,
    `customer_name` VARCHAR(100) NOT NULL DEFAULT 'Pelanggan',
    `barber_id` INT UNSIGNED DEFAULT NULL COMMENT 'Kapster yang diminta (opsional)',
    `service_note` VARCHAR(255) DEFAULT NULL COMMENT 'Catatan layanan yang diminta',
    `status` ENUM('waiting','serving','done','skipped') NOT NULL DEFAULT 'waiting',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_queue_barber` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Stok Awal (Contoh Produk Barbershop)
INSERT IGNORE INTO `stock_products` (`name`, `category`, `unit`, `stock_qty`, `min_stock`, `buy_price`, `sell_price`) VALUES
('Pomade Water Based', 'Pomade & Styling', 'botol', 15, 5, 25000, 45000),
('Pomade Oil Based', 'Pomade & Styling', 'botol', 8, 5, 30000, 55000),
('Hair Wax Premium', 'Pomade & Styling', 'botol', 12, 5, 20000, 38000),
('Shampoo Barbershop 500ml', 'Perawatan Rambut', 'botol', 6, 3, 35000, 60000),
('Conditioner 300ml', 'Perawatan Rambut', 'botol', 4, 3, 30000, 50000),
('Pisau Cukur Disposable (isi 10)', 'Peralatan', 'pack', 20, 5, 15000, 0),
('Tisu Rambut Roll', 'Peralatan', 'roll', 30, 10, 5000, 0),
('Kain Cape Potong', 'Peralatan', 'pcs', 8, 2, 50000, 0),
('Sampo Anti Ketombe', 'Perawatan Rambut', 'botol', 3, 3, 40000, 65000),
('Hair Spray Finishing', 'Pomade & Styling', 'kaleng', 5, 3, 28000, 48000);
