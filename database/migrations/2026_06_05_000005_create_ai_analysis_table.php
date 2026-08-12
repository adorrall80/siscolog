<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS ai_analysis_outputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NULL,
    source_type VARCHAR(80) NOT NULL,
    source_ids JSON NULL,
    model VARCHAR(120) NULL,
    prompt_version VARCHAR(50) NOT NULL,
    prompt_text LONGTEXT NULL,
    output_json JSON NOT NULL,
    final_text LONGTEXT NULL,
    selected_source VARCHAR(30) NOT NULL DEFAULT 'ia',
    review_status VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    professional_notes TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_ai_patient_requested_by (patient_id, requested_by),
    CONSTRAINT fk_ai_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ai_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS ai_analysis_outputs;',
];
