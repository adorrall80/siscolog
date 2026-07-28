<?php

declare(strict_types=1);

return [
    'up' => "CREATE TABLE IF NOT EXISTS clinical_session_topics (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        clinical_session_id BIGINT UNSIGNED NOT NULL,
        session_topic_type_id INT UNSIGNED NOT NULL,
        session_topic_subtype_id INT UNSIGNED NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        UNIQUE KEY uq_session_topic (clinical_session_id, session_topic_type_id, session_topic_subtype_id),
        CONSTRAINT fk_clinical_session_topics_session
            FOREIGN KEY (clinical_session_id) REFERENCES clinical_sessions(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_clinical_session_topics_type
            FOREIGN KEY (session_topic_type_id) REFERENCES session_topic_types(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_clinical_session_topics_subtype
            FOREIGN KEY (session_topic_subtype_id) REFERENCES session_topic_subtypes(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => 'DROP TABLE IF EXISTS clinical_session_topics;',
];
