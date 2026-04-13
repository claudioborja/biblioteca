-- 012_create_search_log.sql
CREATE TABLE IF NOT EXISTS search_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL     DEFAULT NULL,
    query       VARCHAR(255)    NOT NULL,
    results     INT UNSIGNED    NOT NULL DEFAULT 0,
    filters     JSON            NULL     DEFAULT NULL,
    ip_address  VARCHAR(45)     NULL     DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_search_log_user (user_id),
    INDEX idx_search_log_created (created_at),
    INDEX idx_search_log_query (query(100)),

    CONSTRAINT fk_search_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
