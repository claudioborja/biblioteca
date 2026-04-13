-- 014_create_visits_log.sql
CREATE TABLE IF NOT EXISTS visits_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL     DEFAULT NULL,
    branch_id   INT UNSIGNED NULL     DEFAULT NULL,
    page        VARCHAR(255)    NOT NULL,
    ip_address  VARCHAR(45)     NULL     DEFAULT NULL,
    user_agent  VARCHAR(500)    NULL     DEFAULT NULL,
    referer     VARCHAR(500)    NULL     DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_visits_log_user (user_id),
    INDEX idx_visits_log_branch (branch_id),
    INDEX idx_visits_log_page (page(100)),
    INDEX idx_visits_log_created (created_at),

    CONSTRAINT fk_visits_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_visits_log_branch
        FOREIGN KEY (branch_id) REFERENCES library_branches(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
