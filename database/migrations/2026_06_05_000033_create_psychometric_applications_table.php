<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    instrument_id BIGINT UNSIGNED NOT NULL,
    instrument_version_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,
    applied_at DATETIME NOT NULL,
    total_score INT NULL,
    result_code VARCHAR(80) NULL,
    result_label VARCHAR(160) NULL,
    interpretation TEXT NULL,
    professional_notes TEXT NULL,
    computed_data JSON NULL,
    summary_result_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_applications_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_psychometric_applications_instrument
        FOREIGN KEY (instrument_id) REFERENCES psychometric_instruments(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_psychometric_applications_version
        FOREIGN KEY (instrument_version_id) REFERENCES psychometric_instrument_versions(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_psychometric_applications_professional
        FOREIGN KEY (professional_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_psychometric_applications_summary
        FOREIGN KEY (summary_result_id) REFERENCES psychometric_results(id)
        ON DELETE SET NULL,
    INDEX idx_psychometric_applications_patient (patient_id),
    INDEX idx_psychometric_applications_instrument (instrument_id),
    INDEX idx_psychometric_applications_professional (professional_id),
    INDEX idx_psychometric_applications_summary (summary_result_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_applications;',
];

