---
name: skill-mariadb
description: "**WORKFLOW SKILL** — Professional MariaDB database design, implementation, and maintenance at expert level. USE FOR: relational schema design (normalization, 1NF–5NF, BCNF); data modeling (ER diagrams, entity relationships); index strategy and query optimization (EXPLAIN, EXPLAIN ANALYZE, optimizer hints); stored procedures, functions, triggers, events; views and materialized views; transactions and locking (MVCC, InnoDB row locks, deadlock resolution); partitioning (RANGE, LIST, HASH, KEY, COLUMNS); replication (primary-replica, multi-source, GTID, Galera Cluster); backup and recovery (mysqldump, mariabackup, binary logs, point-in-time recovery); security (users, roles, privileges, encryption, SSL/TLS); performance tuning (InnoDB buffer pool, query cache, connection pooling); migrations and schema evolution; MariaDB-specific features (Temporal Tables, JSON columns, sequence engine, Spider engine, Columnstore); monitoring and maintenance. DO NOT USE FOR: NoSQL databases; frontend or application-layer concerns unrelated to database interaction."
---

# MariaDB — Professional Database Design, Implementation & Maintenance

## Core Philosophy

- **Design before code**: Model the domain correctly first — a poor schema cannot be fixed with clever queries.
- **Normalization by default, denormalization by evidence**: Start normalized; denormalize only after profiling proves it necessary.
- **Indexes are a contract**: Every index has a write cost. Add only what queries demand; remove what queries ignore.
- **Transactions protect invariants**: Business rules that span multiple rows/tables must live inside transactions.
- **Measure before tuning**: `EXPLAIN ANALYZE` and slow query log before any optimization.
- **Backups are not optional**: Verified, tested, automated, offsite.

---

## Data Modeling & Schema Design

### Naming Conventions

| Object | Convention | Example |
|--------|-----------|---------|
| Tables | `snake_case`, plural | `user_accounts` |
| Columns | `snake_case` | `created_at` |
| Primary key | `id` (auto) or `<table>_id` | `id`, `user_id` |
| Foreign keys | `<referenced_table_singular>_id` | `user_id`, `order_id` |
| Indexes | `idx_<table>_<columns>` | `idx_orders_user_id_status` |
| Unique indexes | `uq_<table>_<columns>` | `uq_users_email` |
| Check constraints | `chk_<table>_<rule>` | `chk_orders_amount_positive` |

### Data Type Selection

```sql
-- Integer types (choose smallest that fits)
TINYINT            -- -128 to 127 / 0 to 255
SMALLINT           -- -32768 to 32767
MEDIUMINT          -- -8M to 8M
INT                -- -2B to 2B
BIGINT             -- -9.2E18 to 9.2E18

-- Prefer unsigned for IDs and counts
id INT UNSIGNED NOT NULL AUTO_INCREMENT

-- Exact decimals (never FLOAT/DOUBLE for money)
amount DECIMAL(15, 4) NOT NULL

-- Strings
VARCHAR(255)       -- variable length up to 255 chars
CHAR(2)            -- fixed length (country codes, codes)
TEXT               -- up to 65KB (do not index fully)
MEDIUMTEXT         -- up to 16MB
LONGTEXT           -- up to 4GB

-- Dates and times
DATE               -- '2024-01-15'
TIME               -- '14:30:00'
DATETIME           -- '2024-01-15 14:30:00' (no timezone)
TIMESTAMP          -- auto UTC, range 1970–2038 (use for created_at/updated_at)
DATETIME(6)        -- microsecond precision

-- Boolean
TINYINT(1)         -- MariaDB has no native BOOL; alias of TINYINT(1)

-- UUIDs (store efficiently)
BINARY(16)         -- store UUID_TO_BIN(uuid, 1) — saves 16 bytes vs CHAR(36)

-- JSON (MariaDB 10.2+)
JSON               -- validated JSON, stored as LONGTEXT internally
```

### Canonical Table Template

```sql
CREATE TABLE orders (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED    NOT NULL,
    status        ENUM('pending','confirmed','shipped','cancelled','refunded')
                                  NOT NULL DEFAULT 'pending',
    total_amount  DECIMAL(15, 4)  NOT NULL CHECK (total_amount >= 0),
    currency      CHAR(3)         NOT NULL DEFAULT 'USD',
    notes         TEXT            NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME        NULL,                      -- soft delete

    PRIMARY KEY (id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_orders_user_id_status (user_id, status),
    INDEX idx_orders_created_at (created_at),
    INDEX idx_orders_deleted_at (deleted_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
```

### Normalization Quick Reference

| Form | Rule |
|------|------|
| 1NF | Atomic columns; no repeating groups; primary key defined |
| 2NF | 1NF + no partial dependency on composite PK |
| 3NF | 2NF + no transitive dependency on non-key column |
| BCNF | 3NF + every determinant is a candidate key |
| 4NF | BCNF + no multi-valued dependencies |
| 5NF | 4NF + no join dependencies not implied by candidate keys |

---

## Index Strategy

### Index Types

```sql
-- B-Tree (default) — range queries, equality, ORDER BY, GROUP BY
INDEX idx_users_email (email)

-- Unique index — enforces uniqueness + faster lookups
UNIQUE INDEX uq_users_email (email)

-- Composite index — column order matters (leftmost prefix rule)
INDEX idx_orders_user_status_created (user_id, status, created_at)

-- Full-text search
FULLTEXT INDEX ft_products_name_desc (name, description)

-- Prefix index (large VARCHAR/TEXT columns)
INDEX idx_products_slug (slug(50))

-- Invisible index (test impact of dropping without removing)
ALTER TABLE orders ALTER INDEX idx_orders_created_at INVISIBLE;
ALTER TABLE orders ALTER INDEX idx_orders_created_at VISIBLE;
```

### Composite Index Column Order Rules

1. **Equality columns first** (`WHERE user_id = ?`)
2. **Range columns last** (`WHERE created_at > ?`)
3. **Columns used in ORDER BY** after range columns
4. **High cardinality before low cardinality** when both are equality

```sql
-- Query: WHERE user_id = ? AND status = 'pending' ORDER BY created_at
-- Optimal index:
INDEX idx_orders_user_status_created (user_id, status, created_at)
```

### Index Maintenance

```sql
-- Find unused indexes
SELECT
    s.table_name,
    s.index_name,
    s.stat_value AS pages
FROM information_schema.INDEX_STATISTICS s
WHERE s.table_schema = 'mydb'
ORDER BY s.stat_value;

-- Find duplicate indexes
SELECT
    t.TABLE_NAME,
    GROUP_CONCAT(s.INDEX_NAME ORDER BY s.INDEX_NAME) AS indexes
FROM information_schema.TABLES t
JOIN information_schema.STATISTICS s USING (TABLE_NAME, TABLE_SCHEMA)
WHERE t.TABLE_SCHEMA = 'mydb'
GROUP BY t.TABLE_NAME, s.COLUMN_NAME
HAVING COUNT(*) > 1;

-- Rebuild index (online in MariaDB 10.6+)
ALTER TABLE orders ENGINE=InnoDB, ALGORITHM=INPLACE, LOCK=NONE;

-- Update index statistics
ANALYZE TABLE orders;
```

---

## Query Optimization

### EXPLAIN & EXPLAIN ANALYZE

```sql
-- Basic explain
EXPLAIN SELECT * FROM orders WHERE user_id = 1 AND status = 'pending';

-- Extended explain with actual execution stats (MariaDB 10.1+)
EXPLAIN ANALYZE SELECT o.*, u.email
FROM orders o
JOIN users u ON u.id = o.user_id
WHERE o.status = 'pending'
ORDER BY o.created_at DESC
LIMIT 20;

-- JSON format for tooling
EXPLAIN FORMAT=JSON SELECT ...;
```

### EXPLAIN Key Columns

| Column | What to Watch |
|--------|--------------|
| `type` | `ALL` = full scan (bad); `ref`, `range`, `eq_ref`, `const` (good) |
| `key` | Which index was used (`NULL` = none used) |
| `rows` | Estimated rows examined — lower is better |
| `Extra` | `Using filesort`, `Using temporary` = potential problem |
| `filtered` | % of rows passing WHERE after index — low % is a warning |

### Common Query Patterns

```sql
-- Pagination (offset becomes slow at large pages — use keyset instead)
-- BAD for large offsets:
SELECT * FROM orders ORDER BY id LIMIT 20 OFFSET 10000;

-- GOOD: Keyset pagination
SELECT * FROM orders WHERE id > :last_seen_id ORDER BY id LIMIT 20;

-- Covering index query (all columns in index — no table lookup)
SELECT user_id, status, created_at
FROM orders
WHERE user_id = 1
ORDER BY created_at DESC;
-- Index: (user_id, created_at, status) covers the query

-- EXISTS vs IN (EXISTS short-circuits)
SELECT * FROM users u
WHERE EXISTS (
    SELECT 1 FROM orders o WHERE o.user_id = u.id AND o.status = 'pending'
);

-- Avoid functions on indexed columns in WHERE
-- BAD (can't use index on created_at):
WHERE YEAR(created_at) = 2024

-- GOOD:
WHERE created_at >= '2024-01-01' AND created_at < '2025-01-01'

-- Avoid implicit type conversion
-- BAD (user_id is INT, comparison coerces to string):
WHERE user_id = '123'

-- GOOD:
WHERE user_id = 123

-- Optimizer hints (when you know better than the optimizer)
SELECT /*+ USE_INDEX(orders idx_orders_user_status_created) */ *
FROM orders WHERE user_id = 1;

SELECT /*+ NO_ICP(orders) */ * FROM orders WHERE user_id = 1;
```

### Slow Query Log

```ini
# /etc/mysql/mariadb.conf.d/50-server.cnf
slow_query_log          = 1
slow_query_log_file     = /var/log/mysql/slow.log
long_query_time         = 1        # seconds
log_queries_not_using_indexes = 1
min_examined_row_limit  = 100
```

```bash
# Analyze slow query log
mysqldumpslow -s t -t 20 /var/log/mysql/slow.log
pt-query-digest /var/log/mysql/slow.log   # Percona Toolkit
```

---

## Transactions & Locking

### Transaction Isolation Levels

| Level | Dirty Read | Non-Repeatable Read | Phantom Read |
|-------|-----------|---------------------|--------------|
| READ UNCOMMITTED | Yes | Yes | Yes |
| READ COMMITTED | No | Yes | Yes |
| **REPEATABLE READ** | No | No | Yes (MariaDB default) |
| SERIALIZABLE | No | No | No |

```sql
-- Set isolation for a session
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;

-- Explicit transaction
START TRANSACTION;

    UPDATE accounts SET balance = balance - 500 WHERE id = 1;
    UPDATE accounts SET balance = balance + 500 WHERE id = 2;

    -- Verify invariant before commit
    SELECT SUM(balance) INTO @total FROM accounts;
    IF @total < 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invariant violation';
    END IF;

COMMIT;

-- Savepoints
START TRANSACTION;
    INSERT INTO orders ...;
    SAVEPOINT after_order;

    INSERT INTO order_items ...;
    -- Something went wrong with items only
    ROLLBACK TO after_order;

    -- Retry items...
COMMIT;
```

### Locking

```sql
-- Shared lock (FOR SHARE / LOCK IN SHARE MODE)
SELECT * FROM orders WHERE id = 1 FOR SHARE;

-- Exclusive lock (FOR UPDATE)
SELECT * FROM inventory WHERE product_id = 42 FOR UPDATE;

-- Skip locked rows (non-blocking queue consumer)
SELECT * FROM jobs WHERE status = 'pending'
ORDER BY id LIMIT 1
FOR UPDATE SKIP LOCKED;

-- Table-level locks (avoid in InnoDB — prefer row locks)
LOCK TABLES orders WRITE, users READ;
UNLOCK TABLES;

-- Show current locks
SELECT * FROM information_schema.INNODB_LOCKS;
SELECT * FROM information_schema.INNODB_LOCK_WAITS;

-- Kill a blocking process
SHOW PROCESSLIST;
KILL <process_id>;
```

### Deadlock Handling

```sql
-- Show last deadlock
SHOW ENGINE INNODB STATUS\G

-- Deadlock prevention strategies:
-- 1. Always lock resources in the same order across transactions
-- 2. Use SELECT ... FOR UPDATE to lock early
-- 3. Keep transactions short — commit fast
-- 4. Use lower isolation level if phantom reads are not a concern
-- 5. Retry on deadlock (SQLSTATE '40001') at application level
```

---

## Stored Procedures, Functions & Triggers

### Stored Procedure

```sql
DELIMITER $$

CREATE OR REPLACE PROCEDURE transfer_funds(
    IN  p_from_account_id  INT UNSIGNED,
    IN  p_to_account_id    INT UNSIGNED,
    IN  p_amount           DECIMAL(15,4),
    OUT p_status           VARCHAR(50)
)
BEGIN
    DECLARE v_balance DECIMAL(15,4);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status = 'ERROR';
        RESIGNAL;
    END;

    START TRANSACTION;

        SELECT balance INTO v_balance
        FROM accounts
        WHERE id = p_from_account_id
        FOR UPDATE;

        IF v_balance < p_amount THEN
            ROLLBACK;
            SET p_status = 'INSUFFICIENT_FUNDS';
            LEAVE transfer_funds;
        END IF;

        UPDATE accounts SET balance = balance - p_amount WHERE id = p_from_account_id;
        UPDATE accounts SET balance = balance + p_amount WHERE id = p_to_account_id;

        INSERT INTO fund_transfers (from_account_id, to_account_id, amount, transferred_at)
        VALUES (p_from_account_id, p_to_account_id, p_amount, NOW());

    COMMIT;
    SET p_status = 'OK';
END$$

DELIMITER ;

-- Call
CALL transfer_funds(1, 2, 250.00, @status);
SELECT @status;
```

### Stored Function

```sql
DELIMITER $$

CREATE OR REPLACE FUNCTION calculate_vat(
    p_amount   DECIMAL(15,4),
    p_rate     DECIMAL(5,4)
) RETURNS DECIMAL(15,4)
DETERMINISTIC
NO SQL
BEGIN
    RETURN ROUND(p_amount * p_rate, 4);
END$$

DELIMITER ;

SELECT amount, calculate_vat(amount, 0.21) AS vat FROM orders;
```

### Trigger

```sql
DELIMITER $$

-- Audit trigger: record every price change
CREATE OR REPLACE TRIGGER trg_products_price_audit
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF OLD.price <> NEW.price THEN
        INSERT INTO product_price_history (
            product_id, old_price, new_price, changed_at, changed_by
        ) VALUES (
            NEW.id, OLD.price, NEW.price, NOW(), USER()
        );
    END IF;
END$$

-- Prevent negative balance
CREATE OR REPLACE TRIGGER trg_accounts_no_negative
BEFORE UPDATE ON accounts
FOR EACH ROW
BEGIN
    IF NEW.balance < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Account balance cannot be negative';
    END IF;
END$$

DELIMITER ;
```

### Scheduled Event

```sql
-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Purge soft-deleted records older than 90 days
DELIMITER $$

CREATE OR REPLACE EVENT evt_purge_deleted_records
ON SCHEDULE EVERY 1 DAY
STARTS '2024-01-01 02:00:00'
DO
BEGIN
    DELETE FROM orders
    WHERE deleted_at IS NOT NULL
      AND deleted_at < NOW() - INTERVAL 90 DAY
    LIMIT 10000;               -- batch to avoid long locks
END$$

DELIMITER ;
```

---

## Views

```sql
-- Standard view
CREATE OR REPLACE VIEW v_active_orders AS
SELECT
    o.id,
    o.created_at,
    o.status,
    o.total_amount,
    o.currency,
    u.email          AS user_email,
    u.name           AS user_name,
    COUNT(oi.id)     AS item_count
FROM orders o
JOIN users u ON u.id = o.user_id
JOIN order_items oi ON oi.order_id = o.id
WHERE o.deleted_at IS NULL
GROUP BY o.id, o.created_at, o.status, o.total_amount, o.currency, u.email, u.name;

-- Updatable view (single table, no aggregation, no DISTINCT)
CREATE OR REPLACE VIEW v_active_users AS
SELECT id, email, name, created_at
FROM users
WHERE deleted_at IS NULL
WITH CHECK OPTION;  -- prevents inserting rows that would be invisible in the view
```

---

## Partitioning

```sql
-- RANGE partitioning by year (time-series data)
CREATE TABLE events (
    id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    event_type VARCHAR(50)      NOT NULL,
    payload    JSON             NOT NULL,
    created_at DATETIME         NOT NULL,
    PRIMARY KEY (id, created_at)   -- partition key must be in PK
) ENGINE=InnoDB
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2022 VALUES LESS THAN (2023),
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- LIST partitioning by region
CREATE TABLE orders_regional (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    region_id TINYINT      NOT NULL,
    amount    DECIMAL(15,4),
    PRIMARY KEY (id, region_id)
) ENGINE=InnoDB
PARTITION BY LIST (region_id) (
    PARTITION p_eu  VALUES IN (1, 2, 3),
    PARTITION p_us  VALUES IN (4, 5),
    PARTITION p_apac VALUES IN (6, 7, 8)
);

-- Add partition
ALTER TABLE events ADD PARTITION (
    PARTITION p2025 VALUES LESS THAN (2026)
);

-- Drop old partition (instant, no DELETE needed)
ALTER TABLE events DROP PARTITION p2022;

-- Partition pruning check
EXPLAIN PARTITIONS SELECT * FROM events WHERE created_at >= '2024-01-01';
```

---

## Temporal Tables (MariaDB 10.3+)

```sql
-- System-versioned table — MariaDB tracks full row history automatically
CREATE TABLE products (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255)    NOT NULL,
    price       DECIMAL(15,4)  NOT NULL,
    PRIMARY KEY (id)
) WITH SYSTEM VERSIONING;

-- Query current data
SELECT * FROM products;

-- Query state at a point in time
SELECT * FROM products
FOR SYSTEM_TIME AS OF '2024-06-01 00:00:00';

-- Query all versions of a row
SELECT *, ROW_START, ROW_END
FROM products
FOR SYSTEM_TIME ALL
WHERE id = 42
ORDER BY ROW_START;

-- Query what changed between two dates
SELECT * FROM products
FOR SYSTEM_TIME BETWEEN '2024-01-01' AND '2024-12-31';

-- Partition history to manage storage
ALTER TABLE products
PARTITION BY SYSTEM_TIME (
    PARTITION p_history HISTORY,
    PARTITION p_current CURRENT
);
```

---

## Replication

### Primary / Replica (classic binary log)

```ini
# Primary: /etc/mysql/mariadb.conf.d/50-server.cnf
[mysqld]
server_id           = 1
log_bin             = /var/log/mysql/mysql-bin
binlog_format       = ROW
binlog_row_image    = FULL
expire_logs_days    = 14
sync_binlog         = 1
innodb_flush_log_at_trx_commit = 1
```

```ini
# Replica:
[mysqld]
server_id           = 2
relay_log           = /var/log/mysql/relay-bin
log_slave_updates   = 1
read_only           = 1
```

```sql
-- On primary: create replication user
CREATE USER 'replicator'@'replica-host'
    IDENTIFIED BY 'strong_password';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'replica-host';

-- On replica: configure and start
CHANGE MASTER TO
    MASTER_HOST     = 'primary-host',
    MASTER_USER     = 'replicator',
    MASTER_PASSWORD = 'strong_password',
    MASTER_LOG_FILE = 'mysql-bin.000001',
    MASTER_LOG_POS  = 4;
START SLAVE;

-- Check replication status
SHOW SLAVE STATUS\G
-- Key fields: Seconds_Behind_Master, Last_Error, Slave_IO_Running, Slave_SQL_Running
```

### GTID Replication (MariaDB 10.0+)

```ini
[mysqld]
gtid_strict_mode = 1
log_slave_updates = 1
```

```sql
CHANGE MASTER TO
    MASTER_HOST     = 'primary-host',
    MASTER_USER     = 'replicator',
    MASTER_PASSWORD = 'strong_password',
    MASTER_USE_GTID = slave_pos;
START SLAVE;
```

### Replication Monitoring

```sql
-- Lag monitoring
SELECT TIMESTAMPDIFF(SECOND,
    (SELECT MAX(event_time) FROM mysql.general_log WHERE command_type='Query'),
    NOW()
) AS estimated_lag_seconds;

-- Check for errors
SHOW SLAVE STATUS\G

-- Skip a broken event (use sparingly — investigate root cause first)
STOP SLAVE;
SET GLOBAL SQL_SLAVE_SKIP_COUNTER = 1;
START SLAVE;
```

---

## Backup & Recovery

### mysqldump (logical backup)

```bash
# Full backup with consistency
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --master-data=2 \
    --all-databases \
    | gzip > /backups/full-$(date +%F).sql.gz

# Single database
mysqldump --single-transaction mydb | gzip > /backups/mydb-$(date +%F).sql.gz

# Restore
gunzip < /backups/full-2024-01-15.sql.gz | mysql

# Restore single table
mysql mydb < table_export.sql
```

### mariabackup (physical / hot backup — recommended for large DBs)

```bash
# Install
apt install mariadb-backup

# Full backup
mariabackup --backup \
    --target-dir=/backups/full \
    --user=backup_user \
    --password=strong_password

# Prepare (apply redo log)
mariabackup --prepare --target-dir=/backups/full

# Incremental backup
mariabackup --backup \
    --target-dir=/backups/inc-$(date +%F) \
    --incremental-basedir=/backups/full \
    --user=backup_user \
    --password=strong_password

# Prepare incremental
mariabackup --prepare --target-dir=/backups/full \
    --incremental-dir=/backups/inc-2024-01-16

# Restore
systemctl stop mariadb
rm -rf /var/lib/mysql/*
mariabackup --copy-back --target-dir=/backups/full
chown -R mysql:mysql /var/lib/mysql
systemctl start mariadb
```

### Point-In-Time Recovery (PITR)

```bash
# Restore from last full backup first
mariabackup --copy-back --target-dir=/backups/full

# Apply binary logs from backup endpoint to target time
mysqlbinlog \
    --start-datetime="2024-01-15 08:00:00" \
    --stop-datetime="2024-01-15 11:45:00" \
    /var/log/mysql/mysql-bin.000120 \
    /var/log/mysql/mysql-bin.000121 \
    | mysql

# Apply binary logs from a specific position
mysqlbinlog --start-position=4567 mysql-bin.000120 | mysql
```

### Backup Verification

```bash
# Always test restores periodically
mariabackup --prepare --target-dir=/backups/full
mysqld --datadir=/tmp/test-restore --user=mysql &
mysqladmin -S /tmp/test-restore.sock ping
mysql -S /tmp/test-restore.sock -e "SELECT COUNT(*) FROM mydb.orders"
```

---

## Security

### User & Role Management

```sql
-- Create user with strong authentication
CREATE USER 'app_user'@'app-server-ip'
    IDENTIFIED VIA mysql_native_password USING PASSWORD('str0ng!Pass#2024');

-- Create a role
CREATE ROLE 'app_read';
CREATE ROLE 'app_write';

-- Grant privileges to roles
GRANT SELECT ON mydb.* TO 'app_read';
GRANT SELECT, INSERT, UPDATE, DELETE ON mydb.* TO 'app_write';

-- Assign roles to user
GRANT 'app_read', 'app_write' TO 'app_user'@'app-server-ip';
SET DEFAULT ROLE 'app_read', 'app_write' FOR 'app_user'@'app-server-ip';

-- Principle of least privilege: never GRANT ALL for application users
-- Separate users per responsibility:
--   app_readonly   — SELECT only
--   app_write      — SELECT, INSERT, UPDATE, DELETE
--   app_migrate    — DDL (used only during migrations)
--   backup_user    — RELOAD, LOCK TABLES, REPLICATION CLIENT

-- Show effective privileges
SHOW GRANTS FOR 'app_user'@'app-server-ip';

-- Revoke
REVOKE INSERT ON mydb.orders FROM 'app_write';

-- Remove user
DROP USER 'old_user'@'%';
```

### Encryption

```sql
-- Encryption at rest (InnoDB tablespace encryption — MariaDB 10.1+)
-- Enable in config:
-- plugin_load_add = file_key_management
-- file_key_management_filename = /etc/mysql/encryption/keyfile
-- innodb_encrypt_tables = ON
-- innodb_encrypt_log = ON

-- Create encrypted table
CREATE TABLE sensitive_data (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    data VARCHAR(500) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB ENCRYPTED=YES ENCRYPTION_KEY_ID=1;

-- Encrypt existing table
ALTER TABLE sensitive_data ENCRYPTED=YES ENCRYPTION_KEY_ID=1;

-- Encryption in transit (require SSL)
ALTER USER 'app_user'@'%' REQUIRE SSL;

-- Column-level encryption (application-side with AES)
INSERT INTO users (email, ssn_encrypted)
VALUES ('user@example.com', AES_ENCRYPT('123-45-6789', UNHEX(SHA2('passphrase',256))));

SELECT AES_DECRYPT(ssn_encrypted, UNHEX(SHA2('passphrase',256))) AS ssn FROM users;
```

### Security Hardening Checklist

```sql
-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Remove remote root access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

FLUSH PRIVILEGES;

-- Audit plugin (MariaDB Audit Plugin)
INSTALL PLUGIN server_audit SONAME 'server_audit';
SET GLOBAL server_audit_logging = ON;
SET GLOBAL server_audit_events = 'CONNECT,QUERY_DDL,QUERY_DML_NO_SELECT';
SET GLOBAL server_audit_file_path = '/var/log/mysql/audit.log';
```

---

## Performance Tuning

### InnoDB Configuration

```ini
[mysqld]
# Buffer pool: 70–80% of available RAM for dedicated DB servers
innodb_buffer_pool_size         = 8G
innodb_buffer_pool_instances    = 8       # 1 per GB up to 8

# Redo log
innodb_log_file_size            = 1G
innodb_log_buffer_size          = 64M

# Flush behavior (durability vs performance)
innodb_flush_log_at_trx_commit  = 1       # 1=durable, 2=fast (lose up to 1s on crash)
innodb_flush_method             = O_DIRECT

# I/O capacity (match to storage IOPS)
innodb_io_capacity              = 2000
innodb_io_capacity_max          = 4000

# Concurrency
innodb_read_io_threads          = 8
innodb_write_io_threads         = 8

# Row format
innodb_default_row_format       = DYNAMIC

# Connections
max_connections                 = 300
thread_cache_size               = 100
wait_timeout                    = 300
interactive_timeout             = 300

# Query cache (disabled in MariaDB 10.1.7+ by default — leave off)
query_cache_type                = 0
query_cache_size                = 0

# Temp tables
tmp_table_size                  = 128M
max_heap_table_size             = 128M

# Sort/join buffers (per-session — keep moderate)
sort_buffer_size                = 4M
join_buffer_size                = 4M
read_rnd_buffer_size            = 4M
```

### Performance Schema

```sql
-- Enable performance schema (already ON by default in MariaDB 10.5+)
-- Find top slow queries
SELECT
    DIGEST_TEXT,
    COUNT_STAR              AS executions,
    ROUND(AVG_TIMER_WAIT / 1e9, 2) AS avg_ms,
    ROUND(SUM_TIMER_WAIT  / 1e9, 2) AS total_ms,
    SUM_ROWS_EXAMINED,
    SUM_ROWS_SENT
FROM performance_schema.events_statements_summary_by_digest
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 20;

-- Find tables with most lock waits
SELECT object_name, count_star, sum_timer_wait / 1e9 AS wait_ms
FROM performance_schema.table_lock_waits_summary_by_table
ORDER BY sum_timer_wait DESC
LIMIT 10;

-- Buffer pool hit ratio (should be > 99%)
SELECT
    ROUND(
        (1 - (
            (SELECT variable_value FROM information_schema.GLOBAL_STATUS WHERE variable_name = 'Innodb_buffer_pool_reads') /
            (SELECT variable_value FROM information_schema.GLOBAL_STATUS WHERE variable_name = 'Innodb_buffer_pool_read_requests')
        )) * 100, 2
    ) AS buffer_pool_hit_ratio_pct;

-- Connection usage
SELECT variable_name, variable_value
FROM information_schema.GLOBAL_STATUS
WHERE variable_name IN (
    'Threads_connected', 'Threads_running', 'Max_used_connections',
    'Connections', 'Aborted_connects'
);
```

---

## Schema Migrations

### Migration File Convention

```
migrations/
    V001__create_users_table.sql
    V002__create_orders_table.sql
    V003__add_index_orders_status.sql
    V004__add_deleted_at_to_products.sql
```

### Online Schema Change (zero-downtime)

```bash
# Using pt-online-schema-change (Percona Toolkit) — safe for large tables
pt-online-schema-change \
    --alter "ADD COLUMN phone VARCHAR(20) NULL AFTER email" \
    --execute \
    D=mydb,t=users

# Using gh-ost (GitHub) — even safer for very large tables
gh-ost \
    --host=db-primary \
    --database=mydb \
    --table=users \
    --alter="ADD COLUMN phone VARCHAR(20) NULL" \
    --execute
```

### Safe DDL Patterns

```sql
-- Add nullable column (instant in MariaDB 10.3+ with ALGORITHM=INSTANT)
ALTER TABLE orders
    ADD COLUMN tracking_number VARCHAR(100) NULL,
    ALGORITHM=INSTANT;

-- Add NOT NULL column with default (instant)
ALTER TABLE orders
    ADD COLUMN is_gift TINYINT(1) NOT NULL DEFAULT 0,
    ALGORITHM=INSTANT;

-- Add index online (no table lock)
ALTER TABLE orders
    ADD INDEX idx_orders_tracking (tracking_number),
    ALGORITHM=INPLACE, LOCK=NONE;

-- Rename column (MariaDB 10.5+ safe rename)
ALTER TABLE orders
    RENAME COLUMN old_name TO new_name,
    ALGORITHM=INSTANT;

-- Always test migration on a copy first
CREATE TABLE orders_new LIKE orders;
INSERT INTO orders_new SELECT * FROM orders;
ALTER TABLE orders_new ADD COLUMN ...;
```

---

## Monitoring & Maintenance

### Health Queries

```sql
-- Table sizes
SELECT
    table_name,
    ROUND(data_length / 1024 / 1024, 2)   AS data_mb,
    ROUND(index_length / 1024 / 1024, 2)  AS index_mb,
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_mb,
    table_rows
FROM information_schema.TABLES
WHERE table_schema = 'mydb'
ORDER BY (data_length + index_length) DESC;

-- Fragmentation
SELECT
    table_name,
    ROUND(data_free / 1024 / 1024, 2) AS free_mb
FROM information_schema.TABLES
WHERE table_schema = 'mydb'
  AND data_free > 0
ORDER BY data_free DESC;

-- Reclaim fragmented space
OPTIMIZE TABLE orders;     -- or: ALTER TABLE orders ENGINE=InnoDB;

-- Long running queries
SELECT id, user, host, db, time, state, LEFT(info, 100) AS query
FROM information_schema.PROCESSLIST
WHERE command <> 'Sleep' AND time > 10
ORDER BY time DESC;

-- Check table integrity
CHECK TABLE orders EXTENDED;

-- Repair (MyISAM only; InnoDB recovers automatically)
REPAIR TABLE myisam_table;
```

### Routine Maintenance Schedule

| Frequency | Task |
|-----------|------|
| Daily | Verify backup completed and is valid |
| Daily | Check replication lag and errors |
| Daily | Review slow query log for new problems |
| Weekly | Review index usage (remove unused) |
| Weekly | Check table fragmentation; OPTIMIZE if > 20% free |
| Weekly | Review processlist for recurring long queries |
| Monthly | Test backup restore to staging |
| Monthly | Review and rotate user credentials |
| Monthly | Update MariaDB (minor version) |
| Quarterly | Full schema review — unused tables, columns, indexes |

---

## Workflow

1. **Model first** — Design ER diagram; validate normalization before writing DDL.
2. **Define constraints early** — PKs, FKs, UNIQUEs, CHECKs in the schema, not the application.
3. **Choose types precisely** — `DECIMAL` for money, `DATETIME` vs `TIMESTAMP` with intent, `utf8mb4` always.
4. **Index driven by queries** — Write the critical queries first, then design indexes for them.
5. **Explain everything** — Run `EXPLAIN ANALYZE` on every non-trivial query before releasing.
6. **Transactions for invariants** — Any operation touching multiple rows/tables uses a transaction.
7. **Test migrations on a copy** — Never run ALTER directly on production without staging validation.
8. **Backup before every migration** — Full backup + verify before any DDL in production.
9. **Monitor continuously** — Slow query log, replication lag, buffer pool hit ratio, connection count.
10. **Document decisions** — Schema comments, migration comments, non-obvious index rationale.
