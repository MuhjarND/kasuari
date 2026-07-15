-- Jalankan sebagai root melalui phpMyAdmin atau MySQL pada server Kasuari Pusat.
-- Nilai password harus sama dengan sys/sys_koneksi.php.

CREATE DATABASE IF NOT EXISTS `dok_banding`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'dok_banding'@'localhost' IDENTIFIED BY '123456';
ALTER USER 'dok_banding'@'localhost' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON `dok_banding`.* TO 'dok_banding'@'localhost';
FLUSH PRIVILEGES;
