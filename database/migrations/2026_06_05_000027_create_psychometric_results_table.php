<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS psychometric_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    instrument_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,
    applied_at DATETIME NOT NULL,
    score INT NOT NULL,
    interpretation VARCHAR(255) NULL,
    professional_notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_psychometric_results_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_psychometric_results_instrument
        FOREIGN KEY (instrument_id) REFERENCES psychometric_instruments(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_psychometric_results_professional
        FOREIGN KEY (professional_id) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_psychometric_results_patient (patient_id),
    INDEX idx_psychometric_results_instrument (instrument_id),
    INDEX idx_psychometric_results_professional (professional_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS psychometric_results;',
];
