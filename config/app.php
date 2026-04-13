<?php
// config/app.php
declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME'] ?? 'Biblioteca',
    'env'      => $_ENV['APP_ENV'] ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City',
    'locale'   => $_ENV['APP_LOCALE'] ?? 'es_MX',
];
