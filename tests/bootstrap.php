<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost');
define('TESTING', true);

// Use Composer autoloader (includes app namespaces + PHPUnit)
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables from .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Override with phpunit.xml <env> values already loaded by PHPUnit
date_default_timezone_set('America/Mexico_City');
