<?php
// bootstrap.php
declare(strict_types=1);

// PSR-4 autoloader (no Composer required)
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core\\'         => BASE_PATH . '/app/Core/',
        'Controllers\\'  => BASE_PATH . '/app/Controllers/',
        'Services\\'     => BASE_PATH . '/app/Services/',
        'Repositories\\' => BASE_PATH . '/app/Repositories/',
        'Models\\'       => BASE_PATH . '/app/Models/',
        'Middleware\\'   => BASE_PATH . '/app/Middleware/',
        'Enums\\'        => BASE_PATH . '/app/Enums/',
        'Helpers\\'      => BASE_PATH . '/app/Helpers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// Backward compatibility for legacy helper namespaces that may still be referenced.
if (!class_exists('App\\Helpers\\Icons', false)) {
    class_alias('Helpers\\Icons', 'App\\Helpers\\Icons');
}

// Load Composer autoloader if available
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

// Environment variables
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City');

// Error handling
error_reporting(E_ALL);
$debug = filter_var($_ENV['APP_DEBUG'] ?? '0', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php.log');

// Start session early so static Session methods work throughout the lifecycle
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

set_exception_handler(function (\Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    $debug = filter_var($_ENV['APP_DEBUG'] ?? '0', FILTER_VALIDATE_BOOLEAN);
    if ($debug) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        if (file_exists(BASE_PATH . '/views/errors/500.php')) {
            require BASE_PATH . '/views/errors/500.php';
        } else {
            echo 'Error interno del servidor.';
        }
    }
    exit;
});
