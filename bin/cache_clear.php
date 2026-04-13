#!/usr/bin/env php
<?php
/**
 * bin/cache_clear.php — Limpiar cache
 *
 * Uso: php bin/cache_clear.php
 *      php bin/cache_clear.php --apcu     (solo APCu)
 *      php bin/cache_clear.php --file     (solo file cache)
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Core\Logger;

$logger = new Logger();
$clearApcu = !in_array('--file', $argv, true);
$clearFile = !in_array('--apcu', $argv, true);

$logger->info('Cache clear started', [
    'apcu' => $clearApcu,
    'file' => $clearFile,
]);

// Limpiar APCu
if ($clearApcu && function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cache limpiado.\n";
    $logger->info('APCu cache cleared');
} elseif ($clearApcu) {
    echo "APCu no disponible, omitido.\n";
}

// Limpiar file cache
if ($clearFile) {
    $cacheDir = BASE_PATH . '/storage/cache';

    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        echo "File cache limpiado: {$count} archivo(s) eliminados.\n";
        $logger->info("File cache cleared: {$count} files");
    } else {
        echo "Directorio de cache no existe: {$cacheDir}\n";
    }
}

// Limpiar PDFs temporales (> 1 hora)
$tmpDir = BASE_PATH . '/storage/temp';
if (is_dir($tmpDir)) {
    $tmpFiles = glob($tmpDir . '/*');
    $cleaned  = 0;
    $oneHourAgo = time() - 3600;

    foreach ($tmpFiles as $file) {
        if (is_file($file) && filemtime($file) < $oneHourAgo) {
            unlink($file);
            $cleaned++;
        }
    }

    if ($cleaned > 0) {
        echo "Archivos temporales limpiados: {$cleaned}\n";
        $logger->info("Temp files cleaned: {$cleaned}");
    }
}

echo "Cache limpiado correctamente.\n";
