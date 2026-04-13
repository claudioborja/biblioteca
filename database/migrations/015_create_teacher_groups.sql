-- 015_create_teacher_groups.sql
CREATE TABLE IF NOT EXISTS teacher_groups (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id      INT UNSIGNED NOT NULL,
    name            VARCHAR(150)    NOT NULL,
    description     TEXT            NULL     DEFAULT NULL,
    school_year     VARCHAR(20)     NOT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_teacher_groups_teacher (teacher_id),
    INDEX idx_teacher_groups_year (school_year),

    CONSTRAINT fk_teacher_groups_teacher
        FOREIGN KEY (teacher_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
