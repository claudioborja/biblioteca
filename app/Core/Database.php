<?php
// app/Core/Database.php — Conexión PDO singleton
declare(strict_types=1);

namespace Core;

final class Database
{
    private static ?self $instance = null;
    private \PDO $pdo;

    public function __construct(array $config = [])
    {
        if ($config === []) {
            $config = require BASE_PATH . '/config/database.php';
        }

        $dsn = self::buildMysqlDsn($config);

        try {
            $this->pdo = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
        } catch (\PDOException $e) {
            // On Linux, localhost may resolve to a missing Unix socket.
            if (($config['host'] ?? '') === 'localhost' && self::isSocketResolutionFailure($e)) {
                $fallbackConfig = $config;
                $fallbackConfig['host'] = '127.0.0.1';
                $fallbackDsn = self::buildMysqlDsn($fallbackConfig);

                $this->pdo = new \PDO(
                    $fallbackDsn,
                    $config['username'],
                    $config['password'],
                    $config['options'] ?? []
                );
            } else {
                throw $e;
            }
        }
    }

    private static function buildMysqlDsn(array $config): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
    }

    private static function isSocketResolutionFailure(\PDOException $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, '[2002]') && str_contains($message, 'no such file or directory');
    }

    public static function connect(): \PDO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
