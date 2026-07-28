<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NULL,
    option_id BIGINT UNSIGNED NULL,
    item_order INT UNSIGNED NOT NULL DEFAULT 1,
    question_snapshot TEXT NOT NULL,
    answer_label VARCHAR(180) NULL,
    answer_value TEXT NULL,
    answer_score INT NULL,
    answer_json JSON NULL,
    created_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_answers_application
        FOREIGN KEY (application_id) REFERENCES psychometric_applications(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_psychometric_answers_question
        FOREIGN KEY (question_id) REFERENCES psychometric_questions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_psychometric_answers_option
        FOREIGN KEY (option_id) REFERENCES psychometric_question_options(id)
        ON DELETE SET NULL,
    INDEX idx_psychometric_answers_application (application_id),
    INDEX idx_psychometric_answers_question (question_id),
    INDEX idx_psychometric_answers_option (option_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_answers;',
];

