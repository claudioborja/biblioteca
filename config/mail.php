<?php
// config/mail.php
declare(strict_types=1);

$settings = [];

try {
    if (defined('BASE_PATH')) {
        require_once BASE_PATH . '/app/Core/Database.php';

        $pdo = \Core\Database::connect();
        $rows = $pdo->query(
            "SELECT `key`, `value` FROM system_settings WHERE `key` IN (
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'smtp_from_address',
                'smtp_from_name',
                'smtp_timeout'
            )"
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        if (is_array($rows)) {
            $settings = $rows;
        }
    }
} catch (\Throwable) {
    // Fallback to environment values if DB settings are unavailable.
}

return [
    'driver'       => 'smtp',
    'host'         => $settings['smtp_host'] ?? ($_ENV['MAIL_HOST'] ?? 'localhost'),
    'port'         => (int) ($settings['smtp_port'] ?? ($_ENV['MAIL_PORT'] ?? 587)),
    'username'     => $settings['smtp_username'] ?? ($_ENV['MAIL_USERNAME'] ?? ''),
    'password'     => $settings['smtp_password'] ?? ($_ENV['MAIL_PASSWORD'] ?? ''),
    'encryption'   => $settings['smtp_encryption'] ?? ($_ENV['MAIL_ENCRYPTION'] ?? 'tls'),
    'timeout'      => (int) ($settings['smtp_timeout'] ?? ($_ENV['MAIL_TIMEOUT'] ?? 30)),
    'from_address' => $settings['smtp_from_address'] ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@biblioteca.com'),
    'from_name'    => $settings['smtp_from_name'] ?? ($_ENV['MAIL_FROM_NAME'] ?? 'Biblioteca'),
];
