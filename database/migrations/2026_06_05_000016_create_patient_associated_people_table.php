<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS patient_associated_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id BIGINT UNSIGNED NOT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    participant_type VARCHAR(80) NOT NULL,
    display_name VARCHAR(180) NOT NULL,
    normalized_name VARCHAR(180) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_patient_person_lookup (patient_id, created_by_user_id, participant_type, normalized_name, is_active),
    CONSTRAINT fk_associated_people_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_associated_people_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS patient_associated_people;',
];
