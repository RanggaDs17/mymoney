-- ===================================================
-- DATABASE SQL UNTUK APLIKASI MYMONEY
-- ===================================================

-- 1. Buat Database jika belum ada
CREATE DATABASE IF NOT EXISTS `mymoney` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Gunakan database mymoney
USE `mymoney`;

-- ===================================================
-- 2. TABEL USERS (Menyimpan data pengguna/pemilik akun)
-- ===================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- 3. TABEL CATEGORIES (Menyimpan kategori transaksi)
-- ===================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL DEFAULT NULL,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT `fk_categories_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- 4. TABEL TRANSACTIONS (Menyimpan data pemasukan & pengeluaran)
-- ===================================================
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `type` ENUM('income', 'expense') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relasi Kunci Asing (Foreign Key)
    CONSTRAINT `fk_transactions_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_transactions_category` 
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Index untuk mempercepat query pencarian & filter
    INDEX `idx_trans_user` (`user_id`),
    INDEX `idx_trans_date` (`transaction_date`),
    INDEX `idx_trans_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- 5. TABEL SAVINGS_GOALS (Menyimpan target tabungan user)
-- ===================================================
CREATE TABLE IF NOT EXISTS `savings_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `target_amount` DECIMAL(15,2) NOT NULL,
    `current_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `deadline` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Relasi Kunci Asing (Foreign Key)
    CONSTRAINT `fk_savings_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Index pencarian berdasar user
    INDEX `idx_savings_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- 6. DATA BAWAAN (DEFAULT CATEGORIES)
-- ===================================================
INSERT INTO `categories` (`user_id`, `name`, `type`, `is_default`) VALUES
-- Kategori Pengeluaran (Expense)
(NULL, 'Makanan', 'expense', 1),
(NULL, 'Transportasi', 'expense', 1),
(NULL, 'Bensin', 'expense', 1),
(NULL, 'Belanja', 'expense', 1),
(NULL, 'Tagihan', 'expense', 1),
(NULL, 'Pulsa/Internet', 'expense', 1),
(NULL, 'Hiburan', 'expense', 1),
(NULL, 'Kesehatan', 'expense', 1),
(NULL, 'Pendidikan', 'expense', 1),
(NULL, 'Lainnya', 'expense', 1),

-- Kategori Pemasukan (Income)
(NULL, 'Gaji', 'income', 1),
(NULL, 'Uang Saku', 'income', 1),
(NULL, 'Bonus', 'income', 1),
(NULL, 'Penjualan', 'income', 1),
(NULL, 'Lainnya', 'income', 1);

-- ===================================================
-- 7. DATA AKUN DEMO BAWAAN (OPSIONAL)
-- Email: demo@mymoney.com | Password: password123
-- ===================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
(1, 'Pengguna Demo', 'demo@mymoney.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

