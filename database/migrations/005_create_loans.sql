-- 005_create_loans.sql
CREATE TABLE IF NOT EXISTS loans (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    resource_id             INT UNSIGNED    NOT NULL,
    user_id             INT UNSIGNED    NOT NULL,
    librarian_id        INT UNSIGNED    NULL,
    branch_id           INT UNSIGNED    NULL,
    loan_at             DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_at              DATETIME        NOT NULL,
    returned_at         DATETIME        NULL,
    loan_hours_applied  SMALLINT        NOT NULL DEFAULT 72,
    renewals_count      TINYINT         NOT NULL DEFAULT 0,
    status              ENUM('active','returned','overdue','lost') NOT NULL DEFAULT 'active',
    notes               TEXT            NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_loans_resource FOREIGN KEY (resource_id) REFERENCES resources (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_librarian FOREIGN KEY (librarian_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_loans_branch FOREIGN KEY (branch_id) REFERENCES library_branches (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_loans_user_status (user_id, status),
    INDEX idx_loans_due_status (due_at, status),
    INDEX idx_loans_branch_status (branch_id, status),
    INDEX idx_loans_resource_status (resource_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
