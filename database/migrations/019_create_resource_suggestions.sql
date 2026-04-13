-- 019_create_resource_suggestions.sql
CREATE TABLE IF NOT EXISTS resource_suggestions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    author          VARCHAR(255)    NULL     DEFAULT NULL,
    isbn            VARCHAR(17)     NULL     DEFAULT NULL,
    publisher       VARCHAR(200)    NULL     DEFAULT NULL,
    reason          TEXT            NULL     DEFAULT NULL,
    status          ENUM('pending','approved','rejected','acquired') NOT NULL DEFAULT 'pending',
    admin_notes     TEXT            NULL     DEFAULT NULL,
    reviewed_by     INT UNSIGNED NULL     DEFAULT NULL,
    reviewed_at     DATETIME        NULL     DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_resource_suggestions_user (user_id),
    INDEX idx_resource_suggestions_status (status),
    INDEX idx_resource_suggestions_reviewer (reviewed_by),

    CONSTRAINT fk_resource_suggestions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_resource_suggestions_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
