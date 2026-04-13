-- 029_add_app_url_setting.sql
INSERT INTO system_settings (`key`, `value`, `type`)
VALUES ('app_url', '', 'string')
ON DUPLICATE KEY UPDATE `type` = VALUES(`type`);
