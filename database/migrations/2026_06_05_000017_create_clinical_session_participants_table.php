<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS clinical_session_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinical_session_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    participant_type VARCHAR(80) NOT NULL,
    participant_text VARCHAR(220) NOT NULL,
    origin CHAR(1) NOT NULL DEFAULT 'L',
    associated_person_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_session_participant_text (clinical_session_id, participant_text),
    CONSTRAINT fk_session_participants_session
        FOREIGN KEY (clinical_session_id) REFERENCES clinical_sessions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_participants_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_session_participants_person
        FOREIGN KEY (associated_person_id) REFERENCES patient_associated_people(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS clinical_session_participants;',
];
