---
name: skill-php-logging
description: "**WORKFLOW SKILL** — Professional logging, error handling and production debugging in pure PHP. USE FOR: structured logging with severity levels (DEBUG, INFO, WARNING, ERROR, CRITICAL); file-based log rotation without external tools; centralized exception handler; custom error pages (404, 403, 500); request/response logging for debugging; slow query logging from PHP layer; performance profiling (execution time, memory); audit logging for sensitive operations; log formatting (plaintext, JSON structured); log aggregation and search from CLI; debugging in production without Xdebug; PHP error configuration for development vs production; logging for library events (loans, returns, fines, auth); context-aware logging (request ID, user ID, IP); log monitoring via cron alert. DO NOT USE FOR: Sentry/Datadog/New Relic integration; ELK stack; log shipping to external services."
---

# PHP Logging — Professional Error Handling & Debugging

## Core Philosophy

- **Log what matters, not everything**: DEBUG in development, WARNING+ in production. Logs that nobody reads are noise.
- **Structured over plain text**: JSON logs are grep-able, parseable, and ready for any future aggregator.
- **Context on every entry**: Every log line must answer who (user_id), what (action), where (file:line), when (timestamp).
- **Never log sensitive data**: No passwords, no full credit card numbers, no session tokens in logs.
- **Fail open on logging**: If the logger fails, the application must continue — log errors must never crash the app.

---

## Log Levels (PSR-3 Compatible)

| Level | Value | When to Use |
|-------|-------|-------------|
| `DEBUG`     | 100 | Detailed diagnostic info — development only |
| `INFO`      | 200 | Normal operations: loan created, user logged in |
| `NOTICE`    | 250 | Noteworthy but not a problem: config fallback used |
| `WARNING`   | 300 | Unexpected but recoverable: deprecated call, slow query |
| `ERROR`     | 400 | Runtime error that doesn't stop execution |
| `CRITICAL`  | 500 | Critical condition: DB unreachable, disk full |
| `ALERT`     | 550 | Immediate action needed |
| `EMERGENCY` | 600 | System is unusable |

---

## Logger

```php
<?php
// src/Core/Logger.php
declare(strict_types=1);

namespace Core;

final class Logger
{
    public const DEBUG     = 100;
    public const INFO      = 200;
    public const NOTICE    = 250;
    public const WARNING   = 300;
    public const ERROR     = 400;
    public const CRITICAL  = 500;
    public const ALERT     = 550;
    public const EMERGENCY = 600;

    private static array $levelNames = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::NOTICE    => 'NOTICE',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
        self::CRITICAL  => 'CRITICAL',
        self::ALERT     => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    ];

    private static int    $minLevel  = self::DEBUG;
    private static string $channel   = 'app';
    private static bool   $jsonMode  = false;
    private static ?string $requestId = null;
    private static ?int   $userId    = null;

    public static function configure(array $config): void
    {
        self::$minLevel  = $config['level']    ?? self::DEBUG;
        self::$channel   = $config['channel']  ?? 'app';
        self::$jsonMode  = $config['json']     ?? false;
        self::$requestId = bin2hex(random_bytes(4));
    }

    public static function setUser(?int $userId): void
    {
        self::$userId = $userId;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::write(self::DEBUG, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write(self::INFO, $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::write(self::NOTICE, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write(self::WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write(self::ERROR, $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write(self::CRITICAL, $message, $context);
    }

    public static function exception(\Throwable $e, array $context = []): void
    {
        self::write(self::ERROR, $e->getMessage(), array_merge($context, [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => array_slice(
                array_map(fn($f) => ($f['file'] ?? '') . ':' . ($f['line'] ?? ''), $e->getTrace()),
                0, 10
            ),
        ]));
    }

    private static function write(int $level, string $message, array $context): void
    {
        if ($level < self::$minLevel) return;

        $entry = self::$jsonMode
            ? self::formatJson($level, $message, $context)
            : self::formatText($level, $message, $context);

        $file = self::logFile($level);

        // Fail silently — logging must never crash the app
        @file_put_contents($file, $entry . "\n", FILE_APPEND | LOCK_EX);

        // Mirror CRITICAL+ to error_log
        if ($level >= self::CRITICAL) {
            error_log("[{$_SERVER['HTTP_HOST'] ?? 'cli'}] " . $message);
        }
    }

    private static function formatText(int $level, string $message, array $context): string
    {
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $rid = self::$requestId ? " rid=" . self::$requestId : '';
        $uid = self::$userId    ? " uid=" . self::$userId    : '';

        return sprintf('[%s] %s.%s%s%s: %s%s',
            date('Y-m-d H:i:s'),
            self::$channel,
            self::$levelNames[$level],
            $rid,
            $uid,
            $message,
            $ctx,
        );
    }

    private static function formatJson(int $level, string $message, array $context): string
    {
        return json_encode(array_filter([
            'timestamp'  => date('c'),
            'level'      => self::$levelNames[$level],
            'channel'    => self::$channel,
            'message'    => $message,
            'request_id' => self::$requestId,
            'user_id'    => self::$userId,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'context'    => $context ?: null,
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function logFile(int $level): string
    {
        $dir  = BASE_PATH . '/storage/logs';
        $date = date('Y-m-d');

        return match (true) {
            $level >= self::CRITICAL => "{$dir}/critical-{$date}.log",
            $level >= self::ERROR    => "{$dir}/error-{$date}.log",
            default                  => "{$dir}/app-{$date}.log",
        };
    }
}
```

---

## Exception Handler

```php
<?php
// src/Core/ExceptionHandler.php
declare(strict_types=1);

namespace Core;

final class ExceptionHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handle(\Throwable $e): void
    {
        Logger::exception($e, [
            'url'    => $_SERVER['REQUEST_URI'] ?? 'cli',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        ]);

        if (headers_sent()) {
            echo "\n<!-- Exception: " . get_class($e) . " -->";
            return;
        }

        $code = $e instanceof HttpException ? $e->getStatusCode() : 500;
        http_response_code($code);

        if (self::isDebug()) {
            self::renderDebugPage($e);
        } else {
            self::renderErrorPage($code);
        }

        exit(1);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) return false;

        $level = match ($errno) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR => Logger::CRITICAL,
            E_WARNING, E_CORE_WARNING              => Logger::WARNING,
            E_NOTICE, E_USER_NOTICE                => Logger::NOTICE,
            E_DEPRECATED, E_USER_DEPRECATED        => Logger::DEBUG,
            default                                => Logger::WARNING,
        };

        Logger::write($level, $errstr, ['file' => $errfile, 'line' => $errline]);

        return false; // let PHP handle it too
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            Logger::critical('Fatal error on shutdown', $error);
        }
    }

    private static function renderDebugPage(\Throwable $e): void
    {
        $class   = get_class($e);
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $file    = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        echo <<<HTML
        <!DOCTYPE html><html><head><title>Error</title>
        <style>body{font-family:monospace;margin:2rem;background:#1e1e1e;color:#d4d4d4}
        h1{color:#f48771}h2{color:#9cdcfe}.meta{color:#ce9178}.trace{background:#2d2d2d;
        padding:1rem;overflow:auto;font-size:.85rem;line-height:1.5}</style></head><body>
        <h1>{$class}</h1>
        <p class="meta">{$file}:{$line}</p>
        <h2>{$message}</h2>
        <pre class="trace">{$trace}</pre>
        </body></html>
        HTML;
    }

    private static function renderErrorPage(int $code): void
    {
        $view = BASE_PATH . "/views/errors/{$code}.php";
        if (file_exists($view)) {
            require $view;
        } else {
            require BASE_PATH . '/views/errors/500.php';
        }
    }

    private static function isDebug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
    }
}

// Custom HTTP exception
final class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int { return $this->statusCode; }
}
```

---

## Request Logger (Middleware)

```php
<?php
// src/Middleware/RequestLogger.php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;

final class RequestLogger
{
    public function handle(array $request, callable $next): mixed
    {
        $start = microtime(true);

        Logger::info('Request started', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri'    => $_SERVER['REQUEST_URI'],
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $response = $next($request);

        $ms = round((microtime(true) - $start) * 1000, 2);

        $level = $ms > 2000 ? 'warning' : 'info';
        Logger::$level('Request completed', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri'    => $_SERVER['REQUEST_URI'],
            'ms'     => $ms,
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
        ]);

        return $response;
    }
}
```

---

## Query Logger

```php
<?php
// src/Core/LoggingPdo.php — PDO wrapper that logs slow queries
declare(strict_types=1);

namespace Core;

final class LoggingPdo extends \PDO
{
    private int $slowQueryThresholdMs;

    public function __construct(string $dsn, string $user, string $pass, array $options = [])
    {
        parent::__construct($dsn, $user, $pass, $options);
        $this->slowQueryThresholdMs = (int) ($_ENV['SLOW_QUERY_MS'] ?? 500);
    }

    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        $stmt = parent::prepare($query, $options);
        return $stmt ? new LoggingStatement($stmt, $query, $this->slowQueryThresholdMs) : false;
    }
}

final class LoggingStatement
{
    public function __construct(
        private readonly \PDOStatement $inner,
        private readonly string        $sql,
        private readonly int           $thresholdMs,
    ) {}

    public function execute(?array $params = null): bool
    {
        $start  = microtime(true);
        $result = $this->inner->execute($params);
        $ms     = round((microtime(true) - $start) * 1000, 2);

        if ($ms > $this->thresholdMs) {
            Logger::warning('Slow query detected', [
                'sql'    => substr(preg_replace('/\s+/', ' ', $this->sql), 0, 200),
                'ms'     => $ms,
                'params' => $params ? array_map(fn($v) => is_string($v) ? substr($v, 0, 50) : $v, $params) : null,
            ]);
        }

        return $result;
    }

    public function __call(string $name, array $args): mixed
    {
        return $this->inner->$name(...$args);
    }
}
```

---

## Log Rotation

```php
<?php
// src/Core/LogRotator.php
declare(strict_types=1);

namespace Core;

final class LogRotator
{
    public function __construct(
        private readonly string $logDir  = '',
        private readonly int    $keepDays = 30,
    ) {
        $this->logDir = $logDir ?: BASE_PATH . '/storage/logs';
    }

    public function rotate(): int
    {
        $deleted  = 0;
        $cutoff   = time() - ($this->keepDays * 86400);

        foreach (glob($this->logDir . '/*.log') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function compress(): int
    {
        $compressed = 0;
        $yesterday  = date('Y-m-d', strtotime('-1 day'));

        foreach (glob($this->logDir . "/*-{$yesterday}.log") ?: [] as $file) {
            if (!file_exists($file . '.gz')) {
                $gz = gzopen($file . '.gz', 'wb9');
                gzwrite($gz, file_get_contents($file));
                gzclose($gz);
                unlink($file);
                $compressed++;
            }
        }

        return $compressed;
    }

    public function tail(string $filename, int $lines = 100): array
    {
        $file = $this->logDir . '/' . $filename;
        if (!file_exists($file)) return [];

        $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice($content, -$lines);
    }

    public function search(string $pattern, string $filename): array
    {
        $file = $this->logDir . '/' . $filename;
        if (!file_exists($file)) return [];

        return array_filter(
            file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
            fn($line) => str_contains($line, $pattern),
        );
    }
}
```

---

## Domain Event Logging (Library-Specific)

```php
<?php
// src/Core/AuditLog.php
declare(strict_types=1);

namespace Core;

final class AuditLog
{
    public static function loanCreated(int $loanId, int $userId, int $bookId): void
    {
        Logger::info('Loan created', compact('loanId', 'userId', 'bookId'));
    }

    public static function loanReturned(int $loanId, int $userId, float $fine): void
    {
        Logger::info('Loan returned', compact('loanId', 'userId', 'fine'));
    }

    public static function loanOverdue(int $loanId, int $userId, int $daysOverdue): void
    {
        Logger::warning('Loan overdue', compact('loanId', 'userId', 'daysOverdue'));
    }

    public static function reservationCreated(int $reservationId, int $userId, int $bookId): void
    {
        Logger::info('Reservation created', compact('reservationId', 'userId', 'bookId'));
    }

    public static function fineIssued(int $userId, float $amount, string $reason): void
    {
        Logger::notice('Fine issued', compact('userId', 'amount', 'reason'));
    }

    public static function finePaid(int $userId, float $amount): void
    {
        Logger::info('Fine paid', compact('userId', 'amount'));
    }

    public static function bookAdded(int $bookId, string $title, int $adminId): void
    {
        Logger::info('Book added to catalog', compact('bookId', 'title', 'adminId'));
    }

    public static function bookDeleted(int $bookId, int $adminId): void
    {
        Logger::notice('Book removed from catalog', compact('bookId', 'adminId'));
    }

    public static function userBlocked(int $userId, int $adminId, string $reason): void
    {
        Logger::warning('User account blocked', compact('userId', 'adminId', 'reason'));
    }

    public static function unauthorizedAccess(string $route, ?int $userId): void
    {
        Logger::warning('Unauthorized access attempt', [
            'route'   => $route,
            'user_id' => $userId,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }
}
```

---

## Error Pages

```php
<!-- views/errors/404.php -->
<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada — Biblioteca</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="error-page">
    <h1>404</h1>
    <h2>Página no encontrada</h2>
    <p>La página que busca no existe o ha sido movida.</p>
    <a href="/" class="btn">Volver al inicio</a>
</div>
</body>
</html>
```

```php
<!-- views/errors/403.php -->
<?php http_response_code(403); ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Acceso denegado — Biblioteca</title></head>
<body>
<div class="error-page">
    <h1>403</h1>
    <h2>Acceso denegado</h2>
    <p>No tiene permisos para acceder a esta sección.</p>
    <a href="/">Volver al inicio</a>
</div>
</body>
</html>
```

---

## Bootstrap Configuration

```php
<?php
// bootstrap.php — configure logger on startup
$isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';

Logger::configure([
    'level'   => $isProduction ? Logger::WARNING : Logger::DEBUG,
    'channel' => 'biblioteca',
    'json'    => $isProduction,
]);

ExceptionHandler::register();

// PHP error config
ini_set('display_errors',         $isProduction ? '0' : '1');
ini_set('display_startup_errors', $isProduction ? '0' : '1');
ini_set('log_errors',             '1');
ini_set('error_log',              BASE_PATH . '/storage/logs/php-' . date('Y-m-d') . '.log');
error_reporting(E_ALL);
```

---

## CLI Log Tools

```bash
# Tail the live app log
tail -f storage/logs/app-$(date +%F).log

# Watch only ERRORs and above in real time
tail -f storage/logs/app-$(date +%F).log | grep -E 'ERROR|CRITICAL|ALERT'

# Search for a user's activity
grep '"user_id":42' storage/logs/app-$(date +%F).log

# Find all slow queries today
grep 'Slow query' storage/logs/app-$(date +%F).log

# Parse JSON logs with jq
cat storage/logs/app-$(date +%F).log | jq '. | select(.level=="ERROR")'
cat storage/logs/app-$(date +%F).log | jq -r '[.timestamp, .level, .message] | @tsv'

# Count events by level
grep -oP '"level":"\K[^"]+' storage/logs/app-$(date +%F).log | sort | uniq -c | sort -rn

# Find errors in last hour
awk -v d="$(date -d '1 hour ago' '+%Y-%m-%dT%H')" '$0 > d' storage/logs/error-$(date +%F).log
```

---

## Cron: Log Maintenance

```bash
# crontab — daily at 2am
0 2 * * * php /var/www/biblioteca/bin/log-rotate.php
```

```php
<?php
// bin/log-rotate.php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$rotator   = new \Core\LogRotator(keepDays: 30);
$deleted   = $rotator->rotate();
$compressed = $rotator->compress();

echo date('Y-m-d H:i:s') . " — Log rotation: {$deleted} deleted, {$compressed} compressed.\n";
```

---

## .env for Logging

```dotenv
APP_ENV=production          # local | production
APP_DEBUG=false             # true only in local/staging
LOG_LEVEL=WARNING           # DEBUG | INFO | WARNING | ERROR | CRITICAL
LOG_FORMAT=json             # text | json
SLOW_QUERY_MS=500           # log queries slower than this
```

---

## Workflow

1. **Configurar en bootstrap** — `Logger::configure()` y `ExceptionHandler::register()` antes de cualquier otra cosa.
2. **`AuditLog` para eventos de dominio** — Cada acción significativa del sistema de biblioteca registrada semánticamente.
3. **Nunca loguear datos sensibles** — Sin contraseñas, tokens, números de tarjeta, datos personales completos.
4. **JSON en producción** — Fácil de parsear con `jq`; texto en desarrollo para legibilidad.
5. **Slow query logging** — `SLOW_QUERY_MS=500` en producción; bajar a 100 al optimizar una funcionalidad específica.
6. **Rotación diaria** — Logs por fecha (`app-2024-01-15.log`); comprimir y eliminar vía cron.
7. **Error pages informativas** — El usuario ve mensajes amigables; el desarrollador ve el stack trace solo en DEBUG.
8. **`rid` en cada entrada** — El `request_id` permite seguir toda la cadena de un request en los logs.
