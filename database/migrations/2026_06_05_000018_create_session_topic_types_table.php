<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS session_topic_types (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        created_by_user_id BIGINT UNSIGNED NULL,
        code VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        description TEXT NULL,
        color VARCHAR(30) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX idx_session_topic_types_created_by (created_by_user_id),
        INDEX idx_session_topic_types_visibility (is_public, created_by_user_id, is_active),
        CONSTRAINT fk_session_topic_types_created_by
            FOREIGN KEY (created_by_user_id) REFERENCES users(id)
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS session_topic_types;',
];
