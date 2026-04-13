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

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        $this->pdo = new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options'] ?? []
        );
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
