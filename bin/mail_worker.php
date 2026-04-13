#!/usr/bin/env php
<?php
/**
 * bin/mail_worker.php — Procesar cola de email
 *
 * Cron: cada 5 minutos
 * Uso: php bin/mail_worker.php
 *
 * Reintento automático hasta 3 veces con backoff exponencial.
 * Los correos fallidos se marcan y quedan visibles en el panel Admin.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Core\Database;
use Core\Logger;
use Services\MailQueueService;
use Services\MailService;

$pdo    = Database::connect();
$logger = new Logger();
$maxRetries = 3;
$batchSize  = 20;
$lockName = 'biblioteca_mail_worker';

$logger->info('Mail worker started');

// Evita ejecuciones simultaneas del worker.
$lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 0) AS acquired');
$lockStmt->execute([$lockName]);
$acquired = (int) ($lockStmt->fetchColumn() ?: 0);

if ($acquired !== 1) {
    $logger->info('Mail worker skipped: lock is already held by another process');
    echo "Worker omitido: ya hay otro proceso ejecutandose\n";
    exit(0);
}

$queue = new MailQueueService($pdo);
$mailer = new MailService();

$ts = date('Y-m-d H:i:s');

if (!$mailer->isSmtpConfigured()) {
    $warning = $mailer->smtpWarningMessage();
    $logger->warning($warning, ['worker' => 'mail_worker']);
    echo "[{$ts}] ADVERTENCIA: {$warning}\n";

    $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStmt->execute([$lockName]);
    exit(0);
}

// Obtener correos pendientes programados.
$emails = $queue->fetchPendingBatch($batchSize, $maxRetries);

if (empty($emails)) {
    $logger->info('No pending emails');
    echo "[{$ts}] Sin correos pendientes.\n";
    $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStmt->execute([$lockName]);
    exit(0);
}

// ── Send batch via a single SMTP connection ──────────────────────────────────
$result  = $mailer->sendBatch($emails);
$sent    = 0;
$failed  = 0;

// Build a lookup of attempts per email for retry backoff
$attemptsMap = [];
foreach ($emails as $email) {
    $attemptsMap[(int) $email['id']] = (int) ($email['attempts'] ?? 0);
}

foreach ($result['sent'] as $id) {
    $queue->markSent($id);
    $sent++;
    $logger->info("Email sent: #{$id}");
}

foreach ($result['failed'] as $id => $errorMessage) {
    $attempts = ($attemptsMap[$id] ?? 0) + 1;

    if ($attempts >= $maxRetries) {
        $queue->markFailed($id, $attempts, $errorMessage);
        $logger->error("Email permanently failed: #{$id}", ['error' => $errorMessage]);
    } else {
        // Backoff exponencial: 5min, 25min, 125min
        $delayMinutes = (int) pow(5, $attempts);
        $queue->markRetry($id, $attempts, $errorMessage, $delayMinutes);
        $logger->warning("Email retry scheduled: #{$id}", [
            'attempt'   => $attempts,
            'delay_min' => $delayMinutes,
        ]);
    }

    $failed++;
}

$logger->info("Mail worker finished: {$sent} sent, {$failed} failed");
echo "[{$ts}] Procesados: {$sent} enviados, {$failed} fallidos\n";

// ── Auto-purge sent records older than 30 days ────────────────────────────────
try {
    $purgeStmt = $pdo->prepare(
        "DELETE FROM email_queue WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $purgeStmt->execute();
    $purged = $purgeStmt->rowCount();
    if ($purged > 0) {
        $logger->info("Purged {$purged} old sent records from email_queue");
        echo "[{$ts}] Limpieza: {$purged} registro(s) eliminado(s) (>30 días)\n";
    }
} catch (\Throwable) {
    // Non-critical; never block the worker on purge errors
}

$releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
$releaseStmt->execute([$lockName]);
