-- 010_create_system_settings.sql
CREATE TABLE IF NOT EXISTS system_settings (
    `key`       VARCHAR(100)    NOT NULL,
    `value`     TEXT            NOT NULL DEFAULT '',
    `type`      ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO system_settings (`key`, `value`, `type`) VALUES
    ('loan_hours',              '72',       'integer'),
    ('loan_hours_extended',     '120',      'integer'),
    ('renewal_grace_hours',     '2',        'integer'),
    ('max_loans_per_user',    '3',        'integer'),
    ('max_renewals',            '2',        'integer'),
    ('fine_per_hour',           '0.05',     'decimal'),
    ('reservation_hold_hours',  '48',       'integer'),
    ('block_loans_with_fines',  'true',     'boolean'),
    ('reminder_hours_before',   '24',       'integer'),
    ('new_acquisition_days',    '30',       'integer'),
    ('news_on_home',            '3',        'integer'),
    ('max_fine_multiplier',     '2.0',      'decimal'),
    ('library_name',            '',         'string'),
    ('library_address',         '',         'string'),
    ('library_logo',            '',         'string'),
    ('library_phone',           '',         'string'),
    ('library_email',           '',         'string'),
    ('library_website',         '',         'string'),
    ('library_schedule',        '',         'string'),
    ('library_slogan',          '',         'string'),
    ('library_favicon',         '',         'string'),
    ('smtp_enabled',            'true',     'boolean'),
    ('smtp_host',               '',         'string'),
    ('smtp_port',               '587',      'integer'),
    ('smtp_username',           '',         'string'),
    ('smtp_password',           '',         'string'),
    ('smtp_encryption',         'tls',      'string'),
    ('smtp_from_address',       'no-reply@biblioteca.com', 'string'),
    ('smtp_from_name',          'Biblioteca','string'),
    ('smtp_timeout',            '30',       'integer'),
    ('timezone',                'America/Mexico_City', 'string'),
    ('locale',                  'es_MX',    'string'),
    ('date_format',             'd/m/Y H:i','string'),
    ('carnet_prefix',           'BIB',      'string'),
    ('currency_symbol',         '$',        'string');
