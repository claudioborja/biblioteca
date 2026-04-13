#!/usr/bin/env php
<?php
/**
 * bin/new_acquisition_check.php — Desmarcar nuevas adquisiciones antiguas
 *
 * Cron: diario
 * Uso: php bin/new_acquisition_check.php
 *
 * Desmarca el flag is_new_acquisition en libros cuyo acquired_at supera
 * el plazo configurado (new_acquisition_days, default 30 días).
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Core\Database;
use Core\Logger;

$pdo    = Database::connect();
$logger = new Logger();

$logger->info('New acquisition check started');

// Obtener new_acquisition_days de config
$days = 30;
$stmt = $pdo->prepare("SELECT `value` FROM system_settings WHERE `key` = 'new_acquisition_days'");
$stmt->execute();
$row = $stmt->fetch();
if ($row) {
    $days = (int) $row['value'];
}

// Desmarcar libros cuya adquisición supera el plazo
$updateStmt = $pdo->prepare("
    UPDATE resources
    SET is_new_acquisition = 0,
        updated_at = NOW()
    WHERE is_new_acquisition = 1
      AND acquired_at < DATE_SUB(NOW(), INTERVAL ? DAY)
");
$updateStmt->execute([$days]);

$affected = $updateStmt->rowCount();

$logger->info("New acquisition check finished: {$affected} books unmarked", [
    'threshold_days' => $days,
]);

echo "Nuevas adquisiciones desmarcadas: {$affected} (umbral: {$days} días)\n";
