<?php
// app/Core/Logger.php — Logger estructurado con niveles PSR-3
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

    private static array $levelNames = [
        self::DEBUG    => 'DEBUG',
        self::INFO     => 'INFO',
        self::NOTICE   => 'NOTICE',
        self::WARNING  => 'WARNING',
        self::ERROR    => 'ERROR',
        self::CRITICAL => 'CRITICAL',
    ];

    private static int $minLevel = self::DEBUG;
    private static string $channel = 'app';
    private static bool $jsonMode = false;
    private static ?string $requestId = null;
    private static ?int $userId = null;

    public static function configure(array $config): void
    {
        self::$minLevel  = $config['level'] ?? self::DEBUG;
        self::$channel   = $config['channel'] ?? 'app';
        self::$jsonMode  = $config['json'] ?? false;
        self::$requestId = bin2hex(random_bytes(4));
    }

    public static function setUser(?int $userId): void
    {
        self::$userId = $userId;
    }

    public static function debug(string $message, array $context = []): void   { self::write(self::DEBUG, $message, $context); }
    public static function info(string $message, array $context = []): void    { self::write(self::INFO, $message, $context); }
    public static function notice(string $message, array $context = []): void  { self::write(self::NOTICE, $message, $context); }
    public static function warning(string $message, array $context = []): void { self::write(self::WARNING, $message, $context); }
    public static function error(string $message, array $context = []): void   { self::write(self::ERROR, $message, $context); }
    public static function critical(string $message, array $context = []): void { self::write(self::CRITICAL, $message, $context); }

    public static function exception(\Throwable $e, array $context = []): void
    {
        self::write(self::ERROR, $e->getMessage(), array_merge($context, [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]));
    }

    private static function write(int $level, string $message, array $context): void
    {
        if ($level < self::$minLevel) {
            return;
        }

        $entry = self::$jsonMode
            ? self::formatJson($level, $message, $context)
            : self::formatText($level, $message, $context);

        $file = self::logFile($level);
        @file_put_contents($file, $entry . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function formatText(int $level, string $message, array $context): string
    {
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        return sprintf('[%s] %s.%s rid=%s uid=%s: %s%s',
            date('Y-m-d H:i:s'),
            self::$channel,
            self::$levelNames[$level],
            self::$requestId ?? '-',
            self::$userId ?? '-',
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
