<?php

declare(strict_types=1);

return [
    'up' => <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(80) NOT NULL,
    status VARCHAR(80) NOT NULL,
    profession VARCHAR(120) NULL,
    specialty VARCHAR(160) NULL,
    professional_license VARCHAR(120) NULL,
    phone VARCHAR(80) NULL,
    clinic_name VARCHAR(160) NULL,
    clinic_address VARCHAR(220) NULL,
    professional_bio TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
    'down' => 'DROP TABLE IF EXISTS users;',
];
