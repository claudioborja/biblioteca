-- 016_create_teacher_group_students.sql
CREATE TABLE IF NOT EXISTS teacher_group_students (
    group_id    INT UNSIGNED NOT NULL,
    student_id  INT UNSIGNED NOT NULL,
    added_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (group_id, student_id),
    INDEX idx_tgs_student (student_id),

    CONSTRAINT fk_tgs_group
        FOREIGN KEY (group_id) REFERENCES teacher_groups(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tgs_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
