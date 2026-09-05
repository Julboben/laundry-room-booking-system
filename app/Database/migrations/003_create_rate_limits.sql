CREATE TABLE IF NOT EXISTS rate_limits (
    scope VARCHAR(50) NOT NULL,
    subject_hash CHAR(64) NOT NULL,
    failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at BIGINT UNSIGNED NOT NULL,
    locked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (scope, subject_hash),
    INDEX idx_rate_limits_updated_at (updated_at)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
