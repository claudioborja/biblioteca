#!/usr/bin/env php
<?php
/**
 * bin/assignment_check.php — Verificar asignaciones vencidas y recordatorios
 *
 * Cron: diario
 * Uso: php bin/assignment_check.php
 *
 * - 48h antes de la fecha límite: envía recordatorio a alumnos pendientes.
 * - Al vencer: envía resumen de incumplimientos al Docente.
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

$logger->info('Assignment check started');

$remindersSent = 0;
$expiredCount  = 0;

// 1. Recordatorios 48h antes del vencimiento
$reminderStmt = $pdo->query("
    SELECT ra.id, ra.title, ra.due_date, ra.group_id,
           tg.teacher_id,
           b.title AS book_title
    FROM reading_assignments ra
    JOIN teacher_groups tg ON tg.id = ra.group_id
    JOIN resources b ON b.id = ra.resource_id
    WHERE ra.is_active = 1
      AND ra.due_date BETWEEN CURDATE() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
");

foreach ($reminderStmt as $assignment) {
    // Buscar alumnos que aún están pendientes
    $pendingStmt = $pdo->prepare("
                SELECT ras.student_id, u.email, u.name
        FROM reading_assignment_students ras
        JOIN users u ON u.id = ras.student_id
        WHERE ras.assignment_id = ?
          AND ras.status IN ('pending', 'in_progress')
                    AND u.email IS NOT NULL
                    AND u.email <> ''
    ");
    $pendingStmt->execute([$assignment['id']]);
    $pendingStudents = $pendingStmt->fetchAll();

    foreach ($pendingStudents as $student) {
        $mailQueue->enqueueAssignmentReminder($student, $assignment);
        $remindersSent++;
    }

    $logger->info("Assignment reminders: #{$assignment['id']}", [
        'title'   => $assignment['title'],
        'pending' => count($pendingStudents),
    ]);
}

// 2. Asignaciones vencidas: resumen al docente
$expiredStmt = $pdo->query("
    SELECT ra.id, ra.title, ra.due_date, ra.group_id,
           tg.teacher_id,
           u_teacher.email AS teacher_email,
                     u_teacher.name AS teacher_name
    FROM reading_assignments ra
    JOIN teacher_groups tg ON tg.id = ra.group_id
    JOIN users u_teacher ON u_teacher.id = tg.teacher_id
    WHERE ra.is_active = 1
      AND ra.due_date < CURDATE()
            AND u_teacher.email IS NOT NULL
            AND u_teacher.email <> ''
");

foreach ($expiredStmt as $assignment) {
    // Obtener incumplimientos
    $nonCompliantStmt = $pdo->prepare("
                SELECT ras.student_id, u.name, ras.status
        FROM reading_assignment_students ras
        JOIN users u ON u.id = ras.student_id
        WHERE ras.assignment_id = ?
          AND ras.status != 'completed'
    ");
    $nonCompliantStmt->execute([$assignment['id']]);
    $nonCompliant = $nonCompliantStmt->fetchAll();

    if (!empty($nonCompliant)) {
        $mailQueue->enqueueAssignmentOverdueSummary($assignment, $nonCompliant);
        $expiredCount++;

        $logger->info("Assignment overdue summary: #{$assignment['id']}", [
            'title'         => $assignment['title'],
            'non_compliant' => count($nonCompliant),
        ]);
    }

    // Desactivar asignación vencida
    $pdo->prepare("
        UPDATE reading_assignments SET is_active = 0, updated_at = NOW()
        WHERE id = ?
    ")->execute([$assignment['id']]);
}

$logger->info("Assignment check finished", [
    'reminders_sent' => $remindersSent,
    'expired_count'  => $expiredCount,
]);

echo "Recordatorios: {$remindersSent}, Asignaciones vencidas: {$expiredCount}\n";
