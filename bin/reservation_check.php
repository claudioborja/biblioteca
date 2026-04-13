#!/usr/bin/env php
<?php
/**
 * bin/reservation_check.php — Verificar reservas expiradas
 *
 * Cron: cada hora
 * Uso: php bin/reservation_check.php
 *
 * Si un usuario no retira dentro del plazo (reservation_hold_hours, default 48h),
 * la reserva expira y se notifica al siguiente en cola.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Core\Database;
use Core\Logger;
use Services\MailQueueService;

$pdo    = Database::connect();
$logger = new Logger();
$mailQueue = new MailQueueService($pdo);

$logger->info('Reservation check started');

// Obtener reservation_hold_hours de config
$holdHours = 48;
$stmt = $pdo->prepare("SELECT `value` FROM system_settings WHERE `key` = 'reservation_hold_hours'");
$stmt->execute();
$row = $stmt->fetch();
if ($row) {
    $holdHours = (int) $row['value'];
}

// Buscar reservas notificadas cuyo plazo ha expirado
$expiredStmt = $pdo->prepare("
    SELECT r.id, r.resource_id, r.user_id
    FROM reservations r
    WHERE r.status = 'notified'
      AND r.notified_at IS NOT NULL
      AND r.notified_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
");
$expiredStmt->execute([$holdHours]);
$expired = $expiredStmt->fetchAll();

$expiredCount  = 0;
$notifiedCount = 0;

foreach ($expired as $reservation) {
    // Marcar como expirada
    $pdo->prepare("
        UPDATE reservations SET status = 'expired', updated_at = NOW()
        WHERE id = ?
    ")->execute([$reservation['id']]);

    $expiredCount++;
    $logger->info("Reservation expired: #{$reservation['id']}", [
        'resource_id' => $reservation['resource_id'],
        'user_id' => $reservation['user_id'],
    ]);

    // Buscar siguiente en cola para el mismo libro
    $nextStmt = $pdo->prepare("
        SELECT r.id, r.user_id, u.email, u.name, b.title AS resource_title
        FROM reservations r
        JOIN users u ON u.id = r.user_id
        JOIN resources b ON b.id = r.resource_id
        WHERE r.resource_id = ?
          AND r.status = 'waiting'
          AND u.email IS NOT NULL
          AND u.email <> ''
        ORDER BY r.created_at ASC
        LIMIT 1
    ");
    $nextStmt->execute([$reservation['resource_id']]);
    $next = $nextStmt->fetch();

    if ($next) {
        // Marcar como notificada
        $pdo->prepare("
            UPDATE reservations
            SET status = 'notified', notified_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ")->execute([$next['id']]);

        $mailQueue->enqueueReservationReady($next);

        $notifiedCount++;
        $logger->info("Next reservation notified: #{$next['id']}", [
            'user_id' => $next['user_id'],
            'resource_id' => $reservation['resource_id'],
        ]);
    }
}

$logger->info("Reservation check finished: {$expiredCount} expired, {$notifiedCount} next notified");
echo "Reservas: {$expiredCount} expiradas, {$notifiedCount} siguientes notificados\n";
