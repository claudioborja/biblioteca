<?php
// app/Services/MailService.php
declare(strict_types=1);

namespace Services;

use Core\Database;
use Core\Session;

final class MailService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (require BASE_PATH . '/config/mail.php');
    }

    /**
     * @param array<string, mixed> $queuedEmail
     */
    public function sendQueuedEmail(array $queuedEmail): void
    {
        $toEmail = trim((string) ($queuedEmail['to_email'] ?? ''));
        $toName = trim((string) ($queuedEmail['to_name'] ?? ''));
        $subject = trim((string) ($queuedEmail['subject'] ?? ''));
        $bodyHtml = (string) ($queuedEmail['body_html'] ?? '');
        $bodyText = (string) ($queuedEmail['body_text'] ?? '');

        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid recipient email in queue item.');
        }

        if ($subject === '') {
            throw new \RuntimeException('Email subject cannot be empty.');
        }

        if ($bodyHtml === '' && $bodyText === '') {
            throw new \RuntimeException('Email body cannot be empty.');
        }

        $this->send(
            toEmail: $toEmail,
            toName: $toName,
            subject: $subject,
            bodyHtml: $bodyHtml,
            bodyText: $bodyText,
            context: [
                'source' => 'queue',
                'queue_id' => (int) ($queuedEmail['id'] ?? 0),
            ]
        );
    }


    /**
     * Send a batch of queued emails reusing a single SMTP connection.
     * Opens the socket once, authenticates once, sends all emails via RSET
     * between messages, then closes. Avoids the TCP+TLS+AUTH overhead per email.
     *
     * @param  list<array<string, mixed>> $emails  Rows from email_queue
     * @return array{sent: list<int>, failed: array<int, string>}
     */
    public function sendBatch(array $emails): array
    {
        /** @var list<int> $sentIds */
        $sentIds = [];
        /** @var array<int, string> $failedIds */
        $failedIds = [];

        if (empty($emails)) {
            return ['sent' => $sentIds, 'failed' => $failedIds];
        }

        if (!$this->isSmtpConfigured()) {
            $msg = $this->smtpWarningMessage();
            foreach ($emails as $email) {
                $failedIds[(int) ($email['id'] ?? 0)] = $msg;
            }
            return ['sent' => $sentIds, 'failed' => $failedIds];
        }

        $fromAddress = trim((string) ($this->config['from_address'] ?? ''));
        $fromName    = trim((string) ($this->config['from_name']    ?? 'Biblioteca'));
        $host        = trim((string) ($this->config['host']         ?? ''));
        $port        = (int) ($this->config['port']                 ?? 587);
        $username    = trim((string) ($this->config['username']     ?? ''));
        $password    = (string) ($this->config['password']          ?? '');
        $encryption  = strtolower(trim((string) ($this->config['encryption'] ?? 'tls')));
        $timeout     = max(5, (int) ($this->config['timeout']       ?? 30));

        // ── Open socket ──────────────────────────────────────────────────────
        $socketAddr = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $errNo = 0;
        $errStr = '';
        $socket = @stream_socket_client($socketAddr, $errNo, $errStr, $timeout);

        if ($socket === false) {
            $msg = "No se pudo conectar al SMTP {$host}:{$port} — [{$errNo}] {$errStr}";
            foreach ($emails as $email) {
                $failedIds[(int) ($email['id'] ?? 0)] = $msg;
            }
            return ['sent' => $sentIds, 'failed' => $failedIds];
        }

        stream_set_timeout($socket, $timeout);

        try {
            // ── SMTP handshake (once for the whole batch) ────────────────────
            $this->smtpRead($socket); // banner

            $this->smtpCmd($socket, 'EHLO biblioteca.local');

            if ($encryption === 'tls') {
                $resp = $this->smtpCmd($socket, 'STARTTLS');
                if (!str_starts_with($resp, '220')) {
                    throw new \RuntimeException("STARTTLS rechazado: {$resp}");
                }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo activar TLS.');
                }
                $this->smtpCmd($socket, 'EHLO biblioteca.local');
            }

            $this->smtpCmd($socket, 'AUTH LOGIN');
            $this->smtpCmd($socket, base64_encode($username));
            $resp = $this->smtpCmd($socket, base64_encode($password));
            if (!str_starts_with($resp, '235')) {
                throw new \RuntimeException("Autenticación SMTP fallida: {$resp}");
            }

            // ── Send each email on the same authenticated session ────────────
            foreach ($emails as $email) {
                $id = (int) ($email['id'] ?? 0);
                try {
                    $this->sendOnSession($socket, $email, $fromAddress, $fromName);
                    $sentIds[] = $id;
                    $this->auditEmailEvent(
                        'mail_send_success',
                        (string) ($email['to_email'] ?? ''),
                        (string) ($email['subject'] ?? ''),
                        ['source' => 'queue', 'queue_id' => $id]
                    );
                } catch (\Throwable $e) {
                    $failedIds[$id] = $e->getMessage();
                    $this->auditEmailEvent(
                        'mail_send_failed',
                        (string) ($email['to_email'] ?? ''),
                        (string) ($email['subject'] ?? ''),
                        ['source' => 'queue', 'queue_id' => $id, 'error' => $e->getMessage()]
                    );
                    // Recover SMTP state for the next email
                    try {
                        $this->smtpCmd($socket, 'RSET');
                    } catch (\Throwable) {
                        // Socket may be dead; remaining emails will fail naturally
                    }
                }
            }
        } catch (\Throwable $connectErr) {
            // Connection-level failure: mark all not yet processed as failed
            $processedIds = array_merge($sentIds, array_keys($failedIds));
            foreach ($emails as $email) {
                $id = (int) ($email['id'] ?? 0);
                if (!in_array($id, $processedIds, true)) {
                    $failedIds[$id] = $connectErr->getMessage();
                }
            }
        } finally {
            try {
                $this->smtpCmd($socket, 'QUIT');
            } catch (\Throwable) {
                // Ignore QUIT errors
            }
            @fclose($socket);
        }

        return ['sent' => $sentIds, 'failed' => $failedIds];
    }

    /**
     * Send a single email on an already-authenticated SMTP socket.
     * Uses RSET at the end to reset the envelope ready for the next message.
     *
     * @param resource $socket
     * @param array<string, mixed> $email
     */
    private function sendOnSession($socket, array $email, string $fromAddress, string $fromName): void
    {
        $toEmail  = trim((string) ($email['to_email'] ?? ''));
        $toName   = trim((string) ($email['to_name']  ?? ''));
        $subject  = trim((string) ($email['subject']  ?? ''));
        $bodyHtml = (string) ($email['body_html'] ?? '');
        $bodyText = (string) ($email['body_text'] ?? '');

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Dirección inválida: {$toEmail}");
        }

        $plain    = trim($bodyText) !== '' ? $bodyText : $this->toPlainText($bodyHtml);
        $boundary = '=_biblioteca_' . bin2hex(random_bytes(12));
        $html     = $bodyHtml !== ''
            ? $bodyHtml
            : nl2br(htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        // Envelope
        $resp = $this->smtpCmd($socket, "MAIL FROM:<{$fromAddress}>");
        if (!str_starts_with($resp, '250')) {
            throw new \RuntimeException("MAIL FROM rechazado: {$resp}");
        }

        $resp = $this->smtpCmd($socket, "RCPT TO:<{$toEmail}>");
        if (!str_starts_with($resp, '250') && !str_starts_with($resp, '251')) {
            throw new \RuntimeException("RCPT TO rechazado para {$toEmail}: {$resp}");
        }

        $resp = $this->smtpCmd($socket, 'DATA');
        if (!str_starts_with($resp, '354')) {
            throw new \RuntimeException("DATA rechazado: {$resp}");
        }

        $date       = date('r');
        $msgId      = '<' . bin2hex(random_bytes(12)) . '@biblioteca.local>';
        $encSubject = $this->encodeHeader($subject);
        $fromHeader = $this->formatAddress($fromAddress, $fromName);
        $toHeader   = $this->formatAddress($toEmail, $toName);

        $body  = "Date: {$date}\r\n";
        $body .= "From: {$fromHeader}\r\n";
        $body .= "To: {$toHeader}\r\n";
        $body .= "Subject: {$encSubject}\r\n";
        $body .= "Message-ID: {$msgId}\r\n";
        $body .= "MIME-Version: 1.0\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $body .= "X-Mailer: BibliotecaQueue/1.0\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$plain}\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$html}\r\n\r\n";
        $body .= "--{$boundary}--\r\n";
        $body  = preg_replace('/^\.$/m', '..', $body) ?? $body; // dot-stuffing RFC 5321 §4.5.2
        $body .= "\r\n.";

        $resp = $this->smtpCmd($socket, $body);
        if (!str_starts_with($resp, '250')) {
            throw new \RuntimeException("El servidor SMTP rechazó el mensaje: {$resp}");
        }

        // Reset envelope for the next message (does NOT close the session)
        $this->smtpCmd($socket, 'RSET');
    }

    public function isSmtpConfigured(): bool
    {
        return $this->missingSmtpFields() === [];
    }

    public function smtpWarningMessage(): string
    {
        $missing = $this->missingSmtpFields();
        if ($missing === []) {
            return '';
        }

        return 'SMTP no configurado correctamente. Faltan: ' . implode(', ', $missing)
            . '. Configura SMTP en Admin > Settings > Correo SMTP.';
    }

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText = '',
        array $context = []
    ): void
    {
        $steps = null;
        try {
            $this->performSend($toEmail, $toName, $subject, $bodyHtml, $bodyText, $steps);
            $this->auditEmailEvent('mail_send_success', $toEmail, $subject, $context);
        } catch (\Throwable $e) {
            $context['error'] = mb_substr($e->getMessage(), 0, 500);
            $this->auditEmailEvent('mail_send_failed', $toEmail, $subject, $context);
            throw $e;
        }
    }

    /**
     * Send email and return a step-by-step SMTP conversation log for diagnostics.
     *
     * @return array{ok: bool, message: string, steps: list<array{type: string, text: string}>}
     */
    public function sendWithLog(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText = '',
        array $context = []
    ): array
    {
        $steps = [];
        try {
            $this->performSend($toEmail, $toName, $subject, $bodyHtml, $bodyText, $steps);
            $this->auditEmailEvent('mail_send_success', $toEmail, $subject, $context);
            $steps[] = ['type' => 'ok', 'text' => "✓ Correo entregado correctamente a {$toEmail}"];
            return ['ok' => true, 'message' => "Correo enviado correctamente a {$toEmail}.", 'steps' => $steps];
        } catch (\Throwable $e) {
            $context['error'] = mb_substr($e->getMessage(), 0, 500);
            $this->auditEmailEvent('mail_send_failed', $toEmail, $subject, $context);
            $steps[] = ['type' => 'error', 'text' => '✗ ' . $e->getMessage()];
            return ['ok' => false, 'message' => $e->getMessage(), 'steps' => $steps];
        }
    }

    /** @param list<array{type: string, text: string}>|null $steps */
    private function performSend(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText,
        ?array &$steps
    ): void {
        $push = static function (string $type, string $text) use (&$steps): void {
            if ($steps === null) {
                return;
            }
            $text = rtrim($text);
            if ($type === 'recv') {
                foreach (explode("\n", $text) as $line) {
                    $line = rtrim($line);
                    if ($line !== '') {
                        $steps[] = ['type' => 'recv', 'text' => $line];
                    }
                }
                return;
            }
            $steps[] = ['type' => $type, 'text' => $text];
        };

        if (!$this->isSmtpConfigured()) {
            $msg = $this->smtpWarningMessage();
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        $fromAddress = trim((string) ($this->config['from_address'] ?? ''));
        $fromName    = trim((string) ($this->config['from_name']    ?? 'Biblioteca'));

        if ($fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Dirección remitente inválida en la configuración SMTP.';
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $msg = "Dirección de destino inválida: {$toEmail}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        $host       = trim((string) ($this->config['host']       ?? ''));
        $port       = (int) ($this->config['port']               ?? 587);
        $username   = trim((string) ($this->config['username']   ?? ''));
        $password   = (string) ($this->config['password']        ?? '');
        $encryption = strtolower(trim((string) ($this->config['encryption'] ?? 'tls')));
        $timeout    = max(5, (int) ($this->config['timeout']     ?? 30));

        $plain    = trim($bodyText) !== '' ? $bodyText : $this->toPlainText($bodyHtml);
        $boundary = '=_biblioteca_' . bin2hex(random_bytes(12));
        $html     = $bodyHtml !== ''
            ? $bodyHtml
            : nl2br(htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        // ── Open socket ──────────────────────────────────────────────────────
        $socketAddr = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $push('info', "Conectando a {$socketAddr}…");
        $errNo = 0; $errStr = '';
        $socket = @stream_socket_client($socketAddr, $errNo, $errStr, $timeout);
        if ($socket === false) {
            $msg = "No se pudo conectar al servidor SMTP {$host}:{$port} — [{$errNo}] {$errStr}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }
        stream_set_timeout($socket, $timeout);
        $push('info', 'Conexión establecida ✓');

        // ── SMTP conversation ────────────────────────────────────────────────
        $banner = $this->smtpRead($socket);
        $push('recv', $banner);

        $push('send', 'EHLO biblioteca.local');
        $ehlo = $this->smtpCmd($socket, 'EHLO biblioteca.local');
        $push('recv', explode("\n", rtrim($ehlo))[0] ?? $ehlo);

        if ($encryption === 'tls') {
            $push('send', 'STARTTLS');
            $resp = $this->smtpCmd($socket, 'STARTTLS');
            $push('recv', $resp);
            if (!str_starts_with($resp, '220')) {
                fclose($socket);
                $msg = "STARTTLS rechazado: {$resp}";
                $push('error', $msg);
                throw new \RuntimeException($msg);
            }
            $push('info', 'Activando cifrado TLS…');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                $msg = 'No se pudo activar TLS en la conexión SMTP.';
                $push('error', $msg);
                throw new \RuntimeException($msg);
            }
            $push('info', 'TLS activo ✓');
            $push('send', 'EHLO biblioteca.local');
            $ehlo2 = $this->smtpCmd($socket, 'EHLO biblioteca.local');
            $push('recv', explode("\n", rtrim($ehlo2))[0] ?? $ehlo2);
        }

        // AUTH LOGIN
        $push('send', 'AUTH LOGIN');
        $r1 = $this->smtpCmd($socket, 'AUTH LOGIN');
        $push('recv', $r1);

        $maskedUser = preg_replace('/(?<=.{2}).(?=.*@)/', '·', $username) ?? $username;
        $push('send', "USER {$maskedUser}");
        $r2 = $this->smtpCmd($socket, base64_encode($username));
        $push('recv', $r2);

        $push('send', 'PASS ••••••••');
        $resp = $this->smtpCmd($socket, base64_encode($password));
        $push('recv', $resp);
        if (!str_starts_with($resp, '235')) {
            $this->smtpCmd($socket, 'QUIT');
            fclose($socket);
            $msg = "Autenticación SMTP fallida: {$resp}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        // Envelope
        $push('send', "MAIL FROM:<{$fromAddress}>");
        $resp = $this->smtpCmd($socket, "MAIL FROM:<{$fromAddress}>");
        $push('recv', $resp);
        if (!str_starts_with($resp, '250')) {
            $this->smtpCmd($socket, 'QUIT'); fclose($socket);
            $msg = "MAIL FROM rechazado: {$resp}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        $push('send', "RCPT TO:<{$toEmail}>");
        $resp = $this->smtpCmd($socket, "RCPT TO:<{$toEmail}>");
        $push('recv', $resp);
        if (!str_starts_with($resp, '250') && !str_starts_with($resp, '251')) {
            $this->smtpCmd($socket, 'QUIT'); fclose($socket);
            $msg = "RCPT TO rechazado: {$resp}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        // DATA
        $push('send', 'DATA');
        $resp = $this->smtpCmd($socket, 'DATA');
        $push('recv', $resp);
        if (!str_starts_with($resp, '354')) {
            $this->smtpCmd($socket, 'QUIT'); fclose($socket);
            $msg = "DATA rechazado: {$resp}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }

        $push('info', 'Enviando cuerpo del mensaje…');
        $date        = date('r');
        $msgId       = '<' . bin2hex(random_bytes(12)) . '@biblioteca.local>';
        $encSubject  = $this->encodeHeader($subject);
        $fromHeader  = $this->formatAddress($fromAddress, $fromName);
        $toHeader    = $this->formatAddress($toEmail, $toName);

        $body  = "Date: {$date}\r\n";
        $body .= "From: {$fromHeader}\r\n";
        $body .= "To: {$toHeader}\r\n";
        $body .= "Subject: {$encSubject}\r\n";
        $body .= "Message-ID: {$msgId}\r\n";
        $body .= "MIME-Version: 1.0\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $body .= "X-Mailer: BibliotecaQueue/1.0\r\n";
        $body .= "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$plain}\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n{$html}\r\n\r\n";
        $body .= "--{$boundary}--\r\n";
        // Dot-stuffing (RFC 5321 §4.5.2)
        $body = preg_replace('/^\.$/m', '..', $body) ?? $body;
        $body .= "\r\n.";

        $resp = $this->smtpCmd($socket, $body);
        $push('recv', $resp);
        $push('send', 'QUIT');
        $this->smtpCmd($socket, 'QUIT');
        fclose($socket);

        if (!str_starts_with($resp, '250')) {
            $msg = "El servidor SMTP no aceptó el mensaje: {$resp}";
            $push('error', $msg);
            throw new \RuntimeException($msg);
        }
    }

    // ── SMTP helpers ─────────────────────────────────────────────────────────

    /** @param resource $socket */
    private function smtpRead($socket): string
    {
        $full = '';
        while (!feof($socket)) {
            $line = fgets($socket, 1024);
            if ($line === false) {
                break;
            }
            $full .= $line;
            // RFC 5321: last line of a response has a space (or <CR><LF>) at position 3, not a dash.
            if (strlen($line) >= 4 && $line[3] !== '-') {
                break;
            }
        }
        return rtrim($full);
    }

    /** @param resource $socket */
    private function smtpCmd($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpRead($socket);
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        $email = trim($email);
        if ($name === '') {
            return $email;
        }

        return sprintf('%s <%s>', $this->encodeHeader($name), $email);
    }

    private function encodeHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private function toPlainText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function missingSmtpFields(): array
    {
        $missing = [];

        if (trim((string) ($this->config['host'] ?? '')) === '') {
            $missing[] = 'host';
        }

        if ((int) ($this->config['port'] ?? 0) <= 0) {
            $missing[] = 'port';
        }

        if (trim((string) ($this->config['username'] ?? '')) === '') {
            $missing[] = 'username';
        }

        if (trim((string) ($this->config['password'] ?? '')) === '') {
            $missing[] = 'password';
        }

        if (trim((string) ($this->config['from_address'] ?? '')) === '') {
            $missing[] = 'from_address';
        }

        return $missing;
    }

    /** @param array<string, mixed> $context */
    private function auditEmailEvent(string $action, string $toEmail, string $subject, array $context = []): void
    {
        try {
            $pdo = Database::connect();

            $queueIdRaw = $context['queue_id'] ?? null;
            $queueId = is_numeric($queueIdRaw) ? (int) $queueIdRaw : null;
            if ($queueId !== null && $queueId <= 0) {
                $queueId = null;
            }

            $userId = (int) Session::get('auth.user_id', 0);
            if ($userId <= 0) {
                $userId = null;
            }

            $payload = [
                'to_email' => $toEmail,
                'subject' => mb_substr($subject, 0, 250),
                'source' => (string) ($context['source'] ?? 'direct'),
            ];

            if (isset($context['error'])) {
                $payload['error'] = (string) $context['error'];
            }

            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, NULL, ?, ?, ?)'
            );

            $stmt->execute([
                $userId,
                $action,
                'emails',
                $queueId,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'cli'), 0, 255),
            ]);
        } catch (\Throwable) {
            // Never block email delivery if audit logging fails.
        }
    }
}
