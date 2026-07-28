<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS session_topic_type_subtypes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        created_by_user_id BIGINT UNSIGNED NULL,
        session_topic_type_id INT UNSIGNED NOT NULL,
        session_topic_subtype_id INT UNSIGNED NOT NULL,
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX idx_topic_type_subtypes_created_by (created_by_user_id),
        INDEX idx_topic_type_subtypes_scope (session_topic_type_id, is_public, created_by_user_id, is_active),
        CONSTRAINT fk_topic_type_subtypes_created_by
            FOREIGN KEY (created_by_user_id) REFERENCES users(id)
            ON DELETE SET NULL,
        CONSTRAINT fk_topic_type_subtypes_type
            FOREIGN KEY (session_topic_type_id) REFERENCES session_topic_types(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_topic_type_subtypes_subtype
            FOREIGN KEY (session_topic_subtype_id) REFERENCES session_topic_subtypes(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS session_topic_type_subtypes;',
];
