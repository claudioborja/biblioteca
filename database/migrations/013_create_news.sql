-- 013_create_news.sql
CREATE TABLE IF NOT EXISTS news (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title           VARCHAR(255)    NOT NULL,
    slug            VARCHAR(255)    NOT NULL,
    excerpt         VARCHAR(500)    NULL     DEFAULT NULL,
    content         TEXT            NOT NULL,
    cover_image     VARCHAR(500)    NULL     DEFAULT NULL,
    is_published    TINYINT(1)      NOT NULL DEFAULT 0,
    published_at    DATETIME        NULL     DEFAULT NULL,
    author_id       INT UNSIGNED NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_news_slug (slug),
    INDEX idx_news_published (is_published, published_at),
    INDEX idx_news_author (author_id),

    CONSTRAINT fk_news_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
