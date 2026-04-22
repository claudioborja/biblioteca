-- 027_add_smtp_settings.sql
INSERT INTO system_settings (`key`, `value`, `type`) VALUES
    ('smtp_enabled',      'true',                     'boolean'),
    ('smtp_host',         '',                         'string'),
    ('smtp_port',         '587',                      'integer'),
    ('smtp_username',     '',                         'string'),
    ('smtp_password',     '',                         'string'),
    ('smtp_encryption',   'tls',                      'string'),
    ('smtp_from_address', 'no-reply@biblioteca.com',  'string'),
    ('smtp_from_name',    'Biblioteca',               'string'),
    ('smtp_timeout',      '30',                       'integer')
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`);