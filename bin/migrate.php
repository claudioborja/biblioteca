#!/usr/bin/env php
<?php
/**
 * bin/migrate.php — Aplicar migraciones versionadas
 *
 * Uso: php bin/migrate.php
 *      php bin/migrate.php --seed
 *
 * Las migraciones son irreversibles por diseño (sin rollback automático).
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Core\Database;

$pdo = Database::connect();

// Crear tabla migrations si no existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration   VARCHAR(255) NOT NULL,
        executed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Obtener migraciones ya aplicadas
$applied = $pdo->query("SELECT migration FROM migrations ORDER BY id")
    ->fetchAll(\PDO::FETCH_COLUMN);

$appliedSet = array_flip($applied);

// Leer archivos de migración
$migrationsDir = BASE_PATH . '/database/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$newCount = 0;
foreach ($files as $file) {
    $name = basename($file);

    if (isset($appliedSet[$name])) {
        continue;
    }

    echo "Aplicando: {$name} ... ";

    $sql = file_get_contents($file);
    if ($sql === false) {
        echo "ERROR: No se pudo leer el archivo\n";
        exit(1);
    }

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$name]);
        echo "OK\n";
        $newCount++;
    } catch (\PDOException $e) {
        echo "ERROR\n";
        echo "  " . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($newCount === 0) {
    echo "No hay migraciones pendientes.\n";
} else {
    echo "\n{$newCount} migración(es) aplicada(s) correctamente.\n";
}

// Aplicar seeds si se pasa --seed
if (in_array('--seed', $argv, true)) {
    echo "\nAplicando seeds...\n";

    $seedsDir = BASE_PATH . '/database/seeds';
    $seedFiles = glob($seedsDir . '/*.sql');
    sort($seedFiles);

    foreach ($seedFiles as $seedFile) {
        $seedName = basename($seedFile);
        echo "Seed: {$seedName} ... ";

        $sql = file_get_contents($seedFile);
        if ($sql === false) {
            echo "ERROR: No se pudo leer\n";
            continue;
        }

        try {
            $pdo->exec($sql);
            echo "OK\n";
        } catch (\PDOException $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }

    echo "\nSeeds completados.\n";
}
