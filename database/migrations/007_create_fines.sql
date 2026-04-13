-- 007_create_fines.sql
CREATE TABLE IF NOT EXISTS fines (
    id                       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    loan_id                  INT UNSIGNED    NOT NULL,
    user_id                  INT UNSIGNED    NOT NULL,
    amount                   DECIMAL(8,2)    NOT NULL,
    hours_overdue            INT             NOT NULL DEFAULT 0,
    replacement_cost_at_fine DECIMAL(8,2)    NOT NULL,
    reason                   ENUM('overdue','damage','loss') NOT NULL DEFAULT 'overdue',
    status                   ENUM('pending','partially_paid','paid','waived') NOT NULL DEFAULT 'pending',
    amount_paid              DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    waiver_reason            TEXT            NULL,
    waived_by                INT UNSIGNED    NULL,
    created_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_fines_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_fines_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_fines_waived_by FOREIGN KEY (waived_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_fines_user_status (user_id, status),
    INDEX idx_fines_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
