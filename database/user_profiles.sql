-- Jalankan pada database KASUARI, bukan database chatbot.
-- Status block, group, username, dan password pengguna tidak diubah.
CREATE TABLE IF NOT EXISTS sys_user_profiles (
    userid INT(11) NOT NULL PRIMARY KEY,
    photo_path VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jika ada username ganda, ALTER akan ditolak agar akun tidak tergabung diam-diam.
SET @profile_index_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics
           WHERE table_schema = DATABASE() AND table_name = 'sys_users'
             AND index_name = 'ux_sys_users_profile_username'),
    'SELECT 1',
    'ALTER TABLE sys_users ADD UNIQUE KEY ux_sys_users_profile_username (username)'
);
PREPARE profile_index_stmt FROM @profile_index_sql;
EXECUTE profile_index_stmt;
DEALLOCATE PREPARE profile_index_stmt;
