-- 008_create_email_queue.sql
CREATE TABLE IF NOT EXISTS email_queue (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    to_email      VARCHAR(150)    NOT NULL,
    to_name       VARCHAR(150)    NOT NULL DEFAULT '',
    subject       VARCHAR(255)    NOT NULL,
    body_html     MEDIUMTEXT      NOT NULL,
    body_text     MEDIUMTEXT      NULL,
    status        ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts      TINYINT         NOT NULL DEFAULT 0,
    scheduled_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at       DATETIME        NULL,
    error_message TEXT            NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_email_queue_status_scheduled (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
