<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_interpretation_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrument_version_id BIGINT UNSIGNED NOT NULL,
    rule_order INT UNSIGNED NOT NULL DEFAULT 1,
    code VARCHAR(80) NULL,
    label VARCHAR(160) NOT NULL,
    min_score INT NULL,
    max_score INT NULL,
    interpretation TEXT NULL,
    rule_config JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_rules_version
        FOREIGN KEY (instrument_version_id) REFERENCES psychometric_instrument_versions(id)
        ON DELETE CASCADE,
    INDEX idx_psychometric_rules_version (instrument_version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_interpretation_rules;',
];

