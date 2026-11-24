CREATE TABLE IF NOT EXISTS kasuari_sipp_rows (
 pn_id INT UNSIGNED NOT NULL,
 dataset VARCHAR(64) NOT NULL,
 record_key CHAR(64) NOT NULL,
 case_id VARCHAR(32) NULL,
 record_date DATE NULL,
 search_text LONGTEXT NOT NULL,
 data_json LONGTEXT NOT NULL,
 PRIMARY KEY (pn_id, dataset, record_key),
 KEY case_lookup (pn_id, case_id),
 KEY period_lookup (pn_id, dataset, record_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_sipp_stage (
 pn_id INT UNSIGNED NOT NULL,
 request_id CHAR(40) NOT NULL,
 dataset VARCHAR(64) NOT NULL,
 record_key CHAR(64) NOT NULL,
 case_id VARCHAR(32) NULL,
 record_date DATE NULL,
 search_text LONGTEXT NOT NULL,
 data_json LONGTEXT NOT NULL,
 received_at DATETIME NOT NULL,
 PRIMARY KEY (pn_id, request_id, dataset, record_key),
 KEY received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_sipp_state (
 pn_id INT UNSIGNED NOT NULL,
 dataset VARCHAR(64) NOT NULL,
 state VARCHAR(32) NOT NULL,
 row_count INT UNSIGNED NOT NULL DEFAULT 0,
 columns_json TEXT NOT NULL,
 message VARCHAR(500) NOT NULL DEFAULT '',
 checked_at DATETIME NOT NULL,
 synced_at DATETIME NULL,
 request_id CHAR(40) NOT NULL,
 PRIMARY KEY (pn_id, dataset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_sipp_tokens (
 pn_id INT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL,
 created_at DATETIME NOT NULL,
 created_by INT UNSIGNED NOT NULL,
 source_url VARCHAR(500) NOT NULL DEFAULT '',
 PRIMARY KEY (pn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kasuari_sipp_requests (
 generation BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 pn_id INT UNSIGNED NOT NULL,
 request_id CHAR(40) NOT NULL,
 state VARCHAR(16) NOT NULL DEFAULT 'open',
 manifest_hash CHAR(64) NULL,
 started_at DATETIME NOT NULL,
 PRIMARY KEY (generation),
 UNIQUE KEY request_scope (pn_id, request_id),
 KEY completed_scope (pn_id, state, generation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
