<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_instrument_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrument_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    version_label VARCHAR(80) NOT NULL DEFAULT 'v1',
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    scoring_config JSON NULL,
    special_rules JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_versions_instrument
        FOREIGN KEY (instrument_id) REFERENCES psychometric_instruments(id)
        ON DELETE CASCADE,
    UNIQUE KEY psychometric_versions_unique (instrument_id, version_number),
    INDEX idx_psychometric_versions_instrument (instrument_id),
    INDEX idx_psychometric_versions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_instrument_versions;',
];

