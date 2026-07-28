<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS patient_family_node_positions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        patient_id BIGINT UNSIGNED NOT NULL,
        created_by_user_id BIGINT UNSIGNED NULL,
        node_key VARCHAR(120) NOT NULL,
        pos_x DECIMAL(6,2) NOT NULL DEFAULT 50.00,
        pos_y DECIMAL(6,2) NOT NULL DEFAULT 50.00,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        UNIQUE KEY uq_patient_family_node_position (patient_id, created_by_user_id, node_key),
        CONSTRAINT fk_patient_family_node_positions_patient
            FOREIGN KEY (patient_id) REFERENCES patients(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_patient_family_node_positions_user
            FOREIGN KEY (created_by_user_id) REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS patient_family_node_positions;',
];
