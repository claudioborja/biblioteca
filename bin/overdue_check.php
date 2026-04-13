#!/usr/bin/env php
<?php
/**
 * bin/overdue_check.php — Alertas de préstamos vencidos
 *
 * Cron: cada hora
 * Uso: php bin/overdue_check.php
 *
 * Alertas ajustadas al período corto de 72 horas:
 * - 24h antes del vencimiento: correo de recordatorio
 * - Al vencimiento: correo de alerta de mora
 * - 12h después del vencimiento: segunda notificación con monto acumulado
 * Con plazos extendidos (> 5 días), esquema original de 3 días antes.
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

$logger->info('Overdue check started');

$now = new \DateTimeImmutable();
$remindersSent = 0;
$overdueMarked = 0;

// 1. Recordatorio 24h antes del vencimiento.
$stmt = $pdo->query("
        SELECT l.id, l.user_id, l.resource_id, l.due_at,
                     b.title AS resource_title,
                     u.email, u.name
    FROM loans l
    JOIN resources b ON b.id = l.resource_id
    JOIN users u ON u.id = l.user_id
    WHERE l.status = 'active'
      AND l.due_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
            AND u.email IS NOT NULL
            AND u.email <> ''
            AND NOT EXISTS (
                SELECT 1
                FROM email_queue q
                WHERE q.subject = CONCAT('[LOAN_REMINDER][#', l.id, '] Recordatorio de vencimiento')
            )
");

foreach ($stmt as $loan) {
        $mailQueue->enqueueDueReminder($loan);
    $remindersSent++;

    $logger->info("Due reminder sent: loan #{$loan['id']}", [
        'user_id' => $loan['user_id'],
        'due_at'  => $loan['due_at'],
    ]);
}

// 2. Marcar préstamos vencidos como overdue
$overdueStmt = $pdo->query("
    SELECT l.id, l.user_id, l.resource_id, l.due_at,
        b.title AS resource_title,
        u.email, u.name
    FROM loans l
    JOIN resources b ON b.id = l.resource_id
    JOIN users u ON u.id = l.user_id
    WHERE l.status = 'active'
      AND l.due_at < NOW()
");

foreach ($overdueStmt as $loan) {
    $pdo->prepare("UPDATE loans SET status = 'overdue' WHERE id = ?")->execute([$loan['id']]);
    $overdueMarked++;

    $mailQueue->enqueueOverdueAlert($loan);

    // TODO: Generar multa automática según fine_per_hour
    // $fineService->createOverdueFine($loan);

    $logger->info("Loan marked overdue: #{$loan['id']}", [
        'user_id' => $loan['user_id'],
        'due_at'  => $loan['due_at'],
    ]);
}

// 3. Segunda notificación 12h después del vencimiento.
$secondStmt = $pdo->query("
    SELECT l.id, l.user_id, l.resource_id, l.due_at,
                     b.title AS resource_title,
                     u.email, u.name
    FROM loans l
    JOIN resources b ON b.id = l.resource_id
    JOIN users u ON u.id = l.user_id
    WHERE l.status = 'overdue'
      AND l.due_at < DATE_SUB(NOW(), INTERVAL 12 HOUR)
            AND u.email IS NOT NULL
            AND u.email <> ''
            AND NOT EXISTS (
                SELECT 1
                FROM email_queue q
                WHERE q.subject = CONCAT('[LOAN_OVERDUE_SECOND][#', l.id, '] Segunda notificacion de mora')
            )
");

$secondNotices = 0;
foreach ($secondStmt as $loan) {
    $mailQueue->enqueueOverdueSecondNotice($loan);
    $secondNotices++;

    $logger->info("Second overdue notice sent: loan #{$loan['id']}", [
        'user_id' => $loan['user_id'],
    ]);
}

$logger->info("Overdue check finished", [
    'reminders'      => $remindersSent,
    'overdue_marked' => $overdueMarked,
    'second_notices' => $secondNotices,
]);

echo "Recordatorios: {$remindersSent}, Vencidos: {$overdueMarked}, 2da notificación: {$secondNotices}\n";
