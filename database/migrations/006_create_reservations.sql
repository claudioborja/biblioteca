-- 006_create_reservations.sql
CREATE TABLE IF NOT EXISTS reservations (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    resource_id         INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED    NOT NULL,
    queue_position  INT UNSIGNED    NOT NULL,
    status          ENUM('waiting','notified','fulfilled','cancelled','expired') NOT NULL DEFAULT 'waiting',
    notified_at     DATETIME        NULL,
    expires_at      DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_reservations_resource FOREIGN KEY (book_id) REFERENCES resources (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_reservations_resource_queue (resource_id, queue_position, status),
    INDEX idx_reservations_user (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
