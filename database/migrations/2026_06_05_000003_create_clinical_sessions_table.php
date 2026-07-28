<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS clinical_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NULL,
    session_date DATETIME NOT NULL,
    modality VARCHAR(80) NOT NULL,
    reason TEXT NULL,
    subjective_note TEXT NULL,
    objective_note TEXT NULL,
    clinical_impression TEXT NULL,
    risk_level VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    agreements TEXT NULL,
    next_steps TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_sessions_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sessions_professional
        FOREIGN KEY (professional_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS clinical_sessions;',
];
