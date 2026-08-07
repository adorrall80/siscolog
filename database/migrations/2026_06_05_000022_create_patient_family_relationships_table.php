<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS patient_family_relationships (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        patient_id BIGINT UNSIGNED NOT NULL,
        created_by_user_id BIGINT UNSIGNED NULL,
        from_node_key VARCHAR(120) NOT NULL,
        from_node_label VARCHAR(220) NOT NULL,
        relationship_label VARCHAR(120) NOT NULL,
        to_node_key VARCHAR(120) NOT NULL,
        to_node_label VARCHAR(220) NOT NULL,
        is_bidirectional TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        UNIQUE KEY uq_patient_family_relationship (patient_id, created_by_user_id, from_node_key, relationship_label, to_node_key),
        CONSTRAINT fk_patient_family_relationships_patient
            FOREIGN KEY (patient_id) REFERENCES patients(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_patient_family_relationships_user
            FOREIGN KEY (created_by_user_id) REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS patient_family_relationships;',
];
