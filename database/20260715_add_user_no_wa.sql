-- Jalankan pada database Kasuari Pusat sebelum memakai modul pengguna terbaru.
SET NAMES utf8mb4;

SET @no_wa_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sys_users'
    AND COLUMN_NAME = 'no_wa'
);
SET @no_wa_sql = IF(
  @no_wa_exists = 0,
  'ALTER TABLE sys_users ADD COLUMN no_wa VARCHAR(20) NULL AFTER email',
  'SELECT 1'
);
PREPARE no_wa_statement FROM @no_wa_sql;
EXECUTE no_wa_statement;
DEALLOCATE PREPARE no_wa_statement;
