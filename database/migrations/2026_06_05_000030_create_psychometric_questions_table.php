<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instrument_version_id BIGINT UNSIGNED NOT NULL,
    question_group_id BIGINT UNSIGNED NULL,
    item_order INT UNSIGNED NOT NULL DEFAULT 1,
    code VARCHAR(80) NULL,
    label VARCHAR(180) NULL,
    question_text TEXT NOT NULL,
    prompt TEXT NULL,
    question_type VARCHAR(60) NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    min_value INT NULL,
    max_value INT NULL,
    response_fields JSON NULL,
    metadata JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_questions_version
        FOREIGN KEY (instrument_version_id) REFERENCES psychometric_instrument_versions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_psychometric_questions_group
        FOREIGN KEY (question_group_id) REFERENCES psychometric_question_groups(id)
        ON DELETE SET NULL,
    INDEX idx_psychometric_questions_version (instrument_version_id),
    INDEX idx_psychometric_questions_group (question_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_questions;',
];

