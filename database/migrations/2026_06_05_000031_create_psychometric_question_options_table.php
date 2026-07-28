<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_question_options (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id BIGINT UNSIGNED NOT NULL,
    option_order INT UNSIGNED NOT NULL DEFAULT 1,
    label VARCHAR(180) NOT NULL,
    value VARCHAR(180) NOT NULL,
    score INT NULL,
    metadata JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_options_question
        FOREIGN KEY (question_id) REFERENCES psychometric_questions(id)
        ON DELETE CASCADE,
    INDEX idx_psychometric_options_question (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_question_options;',
];

