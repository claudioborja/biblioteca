<?php
// app/Services/MailQueueService.php
declare(strict_types=1);

namespace Services;

use Core\Database;

final class MailQueueService
{
    private \PDO $db;
    private const HIGH_PRIORITY_THRESHOLD = 2;
    private const MANUAL_TRIGGER_COOLDOWN_SECONDS = 20;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function enqueue(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?\DateTimeImmutable $scheduledAt = null,
        int $priority = 5
    ): int {
        $toEmail = trim($toEmail);
        $toName = trim($toName);
        $subject = trim($subject);
        $bodyHtml = trim($bodyHtml);
        $bodyText = $bodyText !== null ? trim($bodyText) : null;

        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid recipient email.');
        }

        if ($subject === '') {
            throw new \InvalidArgumentException('Email subject cannot be empty.');
        }

        if ($bodyHtml === '' && ($bodyText ?? '') === '') {
            throw new \InvalidArgumentException('Email body cannot be empty.');
        }

        $priority = max(1, min(9, $priority));

        $stmt = $this->db->prepare(
            'INSERT INTO email_queue (to_email, to_name, subject, body_html, body_text, priority, status, attempts, scheduled_at, error_message)
             VALUES (?, ?, ?, ?, ?, ?, \'pending\', 0, ?, NULL)'
        );

        $stmt->execute([
            $toEmail,
            $toName,
            $subject,
            $bodyHtml,
            $bodyText,
            $priority,
            ($scheduledAt ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        // For critical notifications, trigger worker in background so delivery
        // does not wait for the next cron tick.
        if ($priority <= self::HIGH_PRIORITY_THRESHOLD) {
            $this->triggerWorkerAsyncWithCooldown();
        }

        return (int) $this->db->lastInsertId();
    }

    private function triggerWorkerAsyncWithCooldown(): void
    {
        if (!function_exists('exec')) {
            return;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('exec', $disabled, true)) {
            return;
        }

        $stateFile = BASE_PATH . '/storage/temp/mail_worker_trigger.state';
        $stateDir = dirname($stateFile);
        if (!is_dir($stateDir) && !@mkdir($stateDir, 0775, true) && !is_dir($stateDir)) {
            return;
        }

        $fp = @fopen($stateFile, 'c+');
        if ($fp === false) {
            return;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                return;
            }

            $raw = stream_get_contents($fp);
            $last = is_string($raw) ? (int) trim($raw) : 0;
            $now = time();

            if ($last > 0 && ($now - $last) < self::MANUAL_TRIGGER_COOLDOWN_SECONDS) {
                return;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) $now);
            fflush($fp);

            $phpBin = PHP_BINARY !== '' ? PHP_BINARY : '/usr/bin/php';
            $worker = BASE_PATH . '/bin/mail_worker.php';
            $cmd = sprintf('%s %s > /dev/null 2>&1 &', escapeshellcmd($phpBin), escapeshellarg($worker));
            exec($cmd);
        } catch (\Throwable) {
            // Never block user flow if trigger fails; cron remains the fallback.
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchPendingBatch(int $limit, int $maxRetries): array
    {
        $limit = max(1, min($limit, 200));
        $maxRetries = max(1, $maxRetries);

                $stmt = $this->db->prepare(
                        'SELECT id, to_email, to_name, subject, body_html, body_text, attempts, priority
                         FROM email_queue
                         WHERE status = \'pending\'
                             AND attempts < ?
                             AND scheduled_at <= NOW()
                         ORDER BY priority ASC, scheduled_at ASC, id ASC
                         LIMIT ?'
                );

        $stmt->bindValue(1, $maxRetries, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows;
    }

    public function markSent(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE email_queue
             SET status = \'sent\', sent_at = NOW(), error_message = NULL
             WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function markRetry(int $id, int $attempts, string $errorMessage, int $delayMinutes): void
    {
        $delayMinutes = max(1, $delayMinutes);
        $stmt = $this->db->prepare(
            'UPDATE email_queue
             SET status = \'pending\',
                 attempts = ?,
                 error_message = ?,
                 scheduled_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
             WHERE id = ?'
        );

        $stmt->execute([
            $attempts,
            mb_substr(trim($errorMessage), 0, 5000),
            $delayMinutes,
            $id,
        ]);
    }

    public function markFailed(int $id, int $attempts, string $errorMessage): void
    {
        $stmt = $this->db->prepare(
            'UPDATE email_queue
             SET status = \'failed\',
                 attempts = ?,
                 error_message = ?
             WHERE id = ?'
        );

        $stmt->execute([
            $attempts,
            mb_substr(trim($errorMessage), 0, 5000),
            $id,
        ]);
    }

    public function enqueueDueReminder(array $loan): int
    {
        $loanId = (int) ($loan['id'] ?? 0);
        $name = trim((string) ($loan['name'] ?? ''));
        $email = trim((string) ($loan['email'] ?? ''));
        $title = trim((string) ($loan['resource_title'] ?? $loan['book_title'] ?? 'recurso'));
        $dueAt = (string) ($loan['due_at'] ?? '');

        $subject = sprintf('[LOAN_REMINDER][#%d] Recordatorio de vencimiento', $loanId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>Te recordamos que tu prestamo de <strong>%s</strong> vence el <strong>%s</strong>.</p><p>Si lo necesitas, realiza la renovacion antes del vencimiento.</p>',
            htmlspecialchars($name !== '' ? $name : 'usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($dueAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $bodyText = sprintf('Hola %s, tu prestamo de "%s" vence el %s. Realiza la renovacion antes del vencimiento.', $name !== '' ? $name : 'usuario', $title, $dueAt);

        return $this->enqueue($email, $name, $subject, $bodyHtml, $bodyText);
    }

    public function enqueueOverdueAlert(array $loan): int
    {
        $loanId = (int) ($loan['id'] ?? 0);
        $name = trim((string) ($loan['name'] ?? ''));
        $email = trim((string) ($loan['email'] ?? ''));
        $title = trim((string) ($loan['resource_title'] ?? $loan['book_title'] ?? 'recurso'));
        $dueAt = (string) ($loan['due_at'] ?? '');

        $subject = sprintf('[LOAN_OVERDUE][#%d] Prestamo vencido', $loanId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>Tu prestamo de <strong>%s</strong> ya se encuentra vencido (fecha limite: <strong>%s</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>',
            htmlspecialchars($name !== '' ? $name : 'usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($dueAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $bodyText = sprintf('Hola %s, tu prestamo de "%s" ya esta vencido (fecha limite: %s). Devuelvelo lo antes posible.', $name !== '' ? $name : 'usuario', $title, $dueAt);

        return $this->enqueue($email, $name, $subject, $bodyHtml, $bodyText);
    }

    public function enqueueOverdueSecondNotice(array $loan): int
    {
        $loanId = (int) ($loan['id'] ?? 0);
        $name = trim((string) ($loan['name'] ?? ''));
        $email = trim((string) ($loan['email'] ?? ''));
        $title = trim((string) ($loan['resource_title'] ?? $loan['book_title'] ?? 'recurso'));
        $dueAt = (string) ($loan['due_at'] ?? '');

        $subject = sprintf('[LOAN_OVERDUE_SECOND][#%d] Segunda notificacion de mora', $loanId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>Segunda notificacion: el prestamo de <strong>%s</strong> continua vencido desde <strong>%s</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>',
            htmlspecialchars($name !== '' ? $name : 'usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($dueAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $bodyText = sprintf('Hola %s, segunda notificacion: el prestamo de "%s" sigue vencido desde %s.', $name !== '' ? $name : 'usuario', $title, $dueAt);

        return $this->enqueue($email, $name, $subject, $bodyHtml, $bodyText);
    }

    public function enqueueReservationReady(array $reservation): int
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        $name = trim((string) ($reservation['name'] ?? ''));
        $email = trim((string) ($reservation['email'] ?? ''));
        $title = trim((string) ($reservation['resource_title'] ?? 'recurso'));

        $subject = sprintf('[RESERVATION_READY][#%d] Tu reserva esta disponible', $reservationId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>Tu reserva del recurso <strong>%s</strong> ya esta disponible para retiro.</p><p>Acercate a la biblioteca dentro del plazo vigente para completar el prestamo.</p>',
            htmlspecialchars($name !== '' ? $name : 'usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $bodyText = sprintf('Hola %s, tu reserva de "%s" ya esta disponible para retiro.', $name !== '' ? $name : 'usuario', $title);

        return $this->enqueue($email, $name, $subject, $bodyHtml, $bodyText);
    }

    public function enqueueAssignmentReminder(array $student, array $assignment): int
    {
        $assignmentId = (int) ($assignment['id'] ?? 0);
        $email = trim((string) ($student['email'] ?? ''));
        $name = trim((string) ($student['name'] ?? ''));
        $title = trim((string) ($assignment['title'] ?? 'Lectura asignada'));
        $resourceTitle = trim((string) ($assignment['book_title'] ?? 'recurso'));
        $dueDate = (string) ($assignment['due_date'] ?? '');

        $subject = sprintf('[ASSIGNMENT_REMINDER][#%d] Recordatorio de lectura', $assignmentId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>Te recordamos que tu asignacion <strong>%s</strong> (recurso: <strong>%s</strong>) vence el <strong>%s</strong>.</p><p>Completa la actividad antes de la fecha limite.</p>',
            htmlspecialchars($name !== '' ? $name : 'estudiante', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($resourceTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($dueDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
        $bodyText = sprintf('Hola %s, tu asignacion "%s" (%s) vence el %s.', $name !== '' ? $name : 'estudiante', $title, $resourceTitle, $dueDate);

        return $this->enqueue($email, $name, $subject, $bodyHtml, $bodyText);
    }

    /**
     * @param list<array<string, mixed>> $nonCompliant
     */
    public function enqueueAssignmentOverdueSummary(array $assignment, array $nonCompliant): int
    {
        $assignmentId = (int) ($assignment['id'] ?? 0);
        $teacherEmail = trim((string) ($assignment['teacher_email'] ?? ''));
        $teacherName = trim((string) ($assignment['teacher_name'] ?? ''));
        $title = trim((string) ($assignment['title'] ?? 'Lectura asignada'));
        $dueDate = (string) ($assignment['due_date'] ?? '');

        $itemsHtml = '';
        $itemsText = [];
        foreach ($nonCompliant as $row) {
            $studentName = trim((string) ($row['name'] ?? 'Estudiante'));
            $status = trim((string) ($row['status'] ?? 'pending'));
            $itemsHtml .= sprintf('<li>%s (%s)</li>', htmlspecialchars($studentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $itemsText[] = sprintf('- %s (%s)', $studentName, $status);
        }

        $subject = sprintf('[ASSIGNMENT_OVERDUE][#%d] Resumen de incumplimientos', $assignmentId);
        $bodyHtml = sprintf(
            '<p>Hola %s,</p><p>La asignacion <strong>%s</strong> (vencimiento: <strong>%s</strong>) tiene estudiantes pendientes:</p><ul>%s</ul>',
            htmlspecialchars($teacherName !== '' ? $teacherName : 'docente', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($dueDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $itemsHtml
        );
        $bodyText = sprintf(
            "Hola %s,\nLa asignacion \"%s\" (vencimiento: %s) tiene estudiantes pendientes:\n%s",
            $teacherName !== '' ? $teacherName : 'docente',
            $title,
            $dueDate,
            implode("\n", $itemsText)
        );

        return $this->enqueue($teacherEmail, $teacherName, $subject, $bodyHtml, $bodyText);
    }
}
