-- 018_create_reading_assignment_students.sql
CREATE TABLE IF NOT EXISTS reading_assignment_students (
    assignment_id   INT UNSIGNED NOT NULL,
    student_id      INT UNSIGNED NOT NULL,
    status          ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    completed_at    DATETIME        NULL     DEFAULT NULL,
    notes           TEXT            NULL     DEFAULT NULL,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (assignment_id, student_id),
    INDEX idx_ras_student (student_id),
    INDEX idx_ras_status (status),

    CONSTRAINT fk_ras_assignment
        FOREIGN KEY (assignment_id) REFERENCES reading_assignments(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ras_student
        FOREIGN KEY (student_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
