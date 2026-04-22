-- 020_create_digital_access_log.sql
CREATE TABLE IF NOT EXISTS digital_access_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resource_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NULL     DEFAULT NULL,
    action      ENUM('view','download') NOT NULL DEFAULT 'view',
    ip_address  VARCHAR(45)     NULL     DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_digital_access_resource (resource_id),
    INDEX idx_digital_access_user (user_id),
    INDEX idx_digital_access_created (created_at),

    CONSTRAINT fk_digital_access_resource
        FOREIGN KEY (resource_id) REFERENCES resources(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_digital_access_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
