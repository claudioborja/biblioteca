-- 009_create_audit_logs.sql
CREATE TABLE IF NOT EXISTS audit_logs (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED    NULL,
    action       VARCHAR(100)    NOT NULL,
    entity_type  VARCHAR(50)     NOT NULL,
    entity_id    INT UNSIGNED    NULL,
    old_values   JSON            NULL,
    new_values   JSON            NULL,
    ip_address   VARCHAR(45)     NOT NULL,
    user_agent   VARCHAR(255)    NOT NULL DEFAULT '',
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_audit_user_action (user_id, action),
    INDEX idx_audit_entity (entity_type, entity_id),
    INDEX idx_audit_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
