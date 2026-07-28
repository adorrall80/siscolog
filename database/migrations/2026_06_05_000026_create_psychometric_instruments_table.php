<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_instruments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    instrument_type_id BIGINT UNSIGNED NULL,
    code VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    measures VARCHAR(220) NULL,
    description TEXT NULL,
    instructions TEXT NULL,
    cautions TEXT NULL,
    frequency VARCHAR(220) NULL,
    min_days INT UNSIGNED NOT NULL DEFAULT 0,
    min_score INT NOT NULL DEFAULT 0,
    max_score INT NOT NULL,
    caution_cutoff INT NULL,
    critical_cutoff INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_instruments_category
        FOREIGN KEY (category_id) REFERENCES psychometric_categories(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_psychometric_instruments_type
        FOREIGN KEY (instrument_type_id) REFERENCES psychometric_instrument_types(id)
        ON DELETE SET NULL,
    INDEX idx_psychometric_instruments_category (category_id),
    INDEX idx_psychometric_instruments_type (instrument_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_instruments;',
];
