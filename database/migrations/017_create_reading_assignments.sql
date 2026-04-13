-- 017_create_reading_assignments.sql
CREATE TABLE IF NOT EXISTS reading_assignments (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_id        INT UNSIGNED NOT NULL,
    resource_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT            NULL     DEFAULT NULL,
    due_date        DATE            NOT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_reading_assignments_group (group_id),
    INDEX idx_reading_assignments_resource resource_id),
    INDEX idx_reading_assignments_due (due_date),

    CONSTRAINT fk_reading_assignments_group
        FOREIGN KEY (group_id) REFERENCES teacher_groups(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reading_assignments_resource
        FOREIGN KEY resource_id) REFERENCES resources(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
