<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS session_topic_subtypes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(140) NOT NULL,
        description TEXT NULL,
        color VARCHAR(30) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX idx_session_topic_subtypes_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS session_topic_subtypes;',
];
