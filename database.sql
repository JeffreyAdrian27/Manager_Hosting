-- Database Schema for Sistem Manager Hosting
CREATE DATABASE IF NOT EXISTS `manager_hosting` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `manager_hosting`;

-- 1. Table users (Admin authentication)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table clients
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `no_telp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `nama_website` VARCHAR(150) NOT NULL,
  `link_hosting` VARCHAR(255) NOT NULL,
  `biaya` DECIMAL(12,2) NOT NULL,
  `gambar` VARCHAR(255) NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_expired` DATE NOT NULL,
  `hosting_dimana` VARCHAR(100) NOT NULL,
  `deleted_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_expired` (`tanggal_expired`),
  INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table email_logs
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT NOT NULL,
  `jenis` ENUM('H-14','H-3','manual') NOT NULL,
  `tanggal_expired` DATE NOT NULL,
  `status` ENUM('berhasil','gagal') NOT NULL,
  `pesan_error` TEXT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  INDEX `idx_unique_reminder` (`client_id`, `jenis`, `tanggal_expired`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table settings
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(50) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Admin: username 'admin', password 'admin123' (bcrypt)
INSERT INTO `users` (`username`, `password`)
VALUES ('admin', '$2y$10$z8Br6up1WafdDC8WDueNNeYvWGouF7rosaB1Na4fyzqmtLU3DWI/.')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed Default Settings
INSERT INTO `settings` (`key`, `value`) VALUES
('admin_email', 'admin@example.com'),
('nama_bank', 'BCA'),
('no_rekening', '1234567890'),
('atas_nama', 'Admin Hosting'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_secure', 'tls'),
('template_email', 'Halo Bapak/Ibu [Nama Klien], semoga sehat selalu.\n\nMenjelang berakhirnya masa aktif website [Nama Website] pada tanggal [Tanggal Expired], kami ingin menginformasikan perihal perpanjangan layanan hosting dan domain agar website tetap aktif dan bisa diakses normal.\n\nBerikut rincian biaya perpanjangannya:\nBiaya Hosting & Pemeliharaan: Rp [Nominal]\n\nSilakan melakukan transfer ke rekening [Nama Bank / E-Wallet] [Nomor Rekening] a.n [Nama Kamu]. Jika sudah, mohon kirimkan bukti transfernya ya. Terima kasih banyak!')
ON DUPLICATE KEY UPDATE `key`=`key`;
