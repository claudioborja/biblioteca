---
name: skill-php-email
description: "**WORKFLOW SKILL** — Professional email sending in pure PHP for library systems. USE FOR: sending emails with PHP mail() native function; PHPMailer integration (lightweight, zero-framework dependency); SMTP configuration for shared hosting and VPS; HTML email templates with inline CSS; plain-text fallback; email queue with file-based or database queue; overdue loan notifications; reservation availability alerts; welcome emails; password reset emails; fine/penalty notices; library membership reminders; bulk email with rate limiting; email logging; bounce handling concepts; SPF/DKIM/DMARC configuration for deliverability; attachments (PDF receipts); email testing in development. DO NOT USE FOR: marketing/bulk campaigns (use SendGrid API directly); push notifications; SMS."
---

# PHP Email — Professional Email System for Library

## Core Philosophy

- **PHPMailer is the one allowed vendor**: Small, battle-tested, no framework required, works on any PHP host.
- **Queue async notifications**: Never send email synchronously during a web request — log to DB queue, process via cron.
- **HTML + plain text always**: Every email must have both versions for accessibility and spam filters.
- **Templates in PHP**: No extra template engine — pure PHP with `ob_start()`.
- **Never expose SMTP credentials**: Always from `.env` / environment config.

---

## PHPMailer Setup (no Composer on restricted hosts)

```bash
# With Composer (VPS)
composer require phpmailer/phpmailer

# Without Composer (shared hosting) — download and include directly
# Download: https://github.com/PHPMailer/PHPMailer/releases
# Place in: src/Vendor/phpmailer/
```

```php
<?php
// Without Composer: manual include
require BASE_PATH . '/src/Vendor/phpmailer/src/Exception.php';
require BASE_PATH . '/src/Vendor/phpmailer/src/PHPMailer.php';
require BASE_PATH . '/src/Vendor/phpmailer/src/SMTP.php';
```

---

## Mail Service

```php
<?php
// src/Services/MailService.php
declare(strict_types=1);

namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

final class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $driver = $_ENV['MAIL_DRIVER'] ?? 'smtp';

        if ($driver === 'mail') {
            $this->mailer->isMail();
        } else {
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['MAIL_HOST']       ?? 'localhost';
            $this->mailer->Port       = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $_ENV['MAIL_USERNAME']   ?? '';
            $this->mailer->Password   = $_ENV['MAIL_PASSWORD']   ?? '';
            $this->mailer->SMTPSecure = match ((int)($_ENV['MAIL_PORT'] ?? 587)) {
                465     => PHPMailer::ENCRYPTION_SMTPS,
                default => PHPMailer::ENCRYPTION_STARTTLS,
            };
            $this->mailer->SMTPDebug = ($_ENV['APP_DEBUG'] ?? false) ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
        }

        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->Encoding = 'base64';
        $this->mailer->setFrom(
            $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@biblioteca.com',
            $_ENV['MAIL_FROM_NAME']    ?? 'Biblioteca',
        );
    }

    /**
     * @param array<string,string> $to  ['email' => 'name']
     */
    public function send(
        array  $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        array  $attachments = [],
    ): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            foreach ($to as $email => $name) {
                $this->mailer->addAddress($email, $name);
            }

            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = $textBody ?: strip_tags($htmlBody);

            foreach ($attachments as $path => $name) {
                $this->mailer->addAttachment($path, $name);
            }

            $this->mailer->send();
            $this->log('sent', array_keys($to), $subject);
            return true;

        } catch (Exception $e) {
            $this->log('failed', array_keys($to), $subject, $e->getMessage());
            return false;
        }
    }

    private function log(string $status, array $recipients, string $subject, string $error = ''): void
    {
        $entry = sprintf(
            "[%s] status=%s to=%s subject=%s %s\n",
            date('Y-m-d H:i:s'),
            $status,
            implode(',', $recipients),
            $subject,
            $error ? "error={$error}" : '',
        );
        file_put_contents(BASE_PATH . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
```

---

## Email Template Engine

```php
<?php
// src/Mail/MailTemplate.php
declare(strict_types=1);

namespace Mail;

final class MailTemplate
{
    public static function render(string $template, array $data = []): array
    {
        extract($data, EXTR_SKIP);

        // HTML version
        ob_start();
        require BASE_PATH . "/views/emails/{$template}.html.php";
        $html = ob_get_clean();

        // Plain text version
        $textFile = BASE_PATH . "/views/emails/{$template}.text.php";
        if (file_exists($textFile)) {
            ob_start();
            require $textFile;
            $text = ob_get_clean();
        } else {
            $text = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html));
        }

        return ['html' => $html, 'text' => $text];
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
```

---

## Email Templates

```php
<!-- views/emails/layout.html.php — base layout for all emails -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= MailTemplate::e($subject ?? '') ?></title>
    <style>
        /* Inline all styles — email clients don't support external CSS */
        body { margin:0; padding:0; background:#f4f4f4; font-family:Arial,sans-serif; }
        .container { max-width:600px; margin:0 auto; background:#ffffff; }
        .header { background:#1e3a5f; padding:24px; text-align:center; }
        .header h1 { color:#ffffff; margin:0; font-size:22px; }
        .body { padding:32px 24px; color:#333333; font-size:15px; line-height:1.6; }
        .body h2 { color:#1e3a5f; margin-top:0; }
        .btn { display:inline-block; background:#1e3a5f; color:#ffffff !important;
               text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold; }
        .alert { background:#fff3cd; border-left:4px solid #f59e0b; padding:12px 16px;
                 margin:16px 0; border-radius:0 4px 4px 0; }
        .alert.danger { background:#fee2e2; border-color:#ef4444; }
        .footer { background:#f4f4f4; padding:16px 24px; text-align:center;
                  font-size:12px; color:#888888; }
        table { border-collapse:collapse; width:100%; }
        th, td { padding:10px 12px; text-align:left; border-bottom:1px solid #eeeeee; }
        th { background:#f8f9fa; font-weight:bold; color:#555555; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📚 Biblioteca Municipal</h1>
    </div>
    <div class="body">
        <?= $content ?>
    </div>
    <div class="footer">
        Este correo fue enviado automáticamente. No responda a este mensaje.<br>
        © <?= date('Y') ?> Biblioteca Municipal — Todos los derechos reservados.
    </div>
</div>
</body>
</html>
```

```php
<!-- views/emails/loan_overdue.html.php -->
<?php
ob_start();
?>
<h2>Préstamo vencido</h2>
<p>Estimado/a <strong><?= MailTemplate::e($userName) ?></strong>,</p>
<p>Le informamos que el siguiente préstamo está <strong>vencido</strong> desde el
   <strong><?= MailTemplate::e($dueDate) ?></strong>:</p>

<table>
    <tr><th>Libro</th><th>Autor</th><th>Fecha de vencimiento</th><th>Días de retraso</th></tr>
    <?php foreach ($loans as $loan): ?>
    <tr>
        <td><?= MailTemplate::e($loan['book_title']) ?></td>
        <td><?= MailTemplate::e($loan['book_author']) ?></td>
        <td><?= MailTemplate::e($loan['due_date']) ?></td>
        <td style="color:#ef4444;font-weight:bold;"><?= MailTemplate::e($loan['days_overdue']) ?> días</td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="alert danger">
    <strong>Multa acumulada:</strong> $<?= MailTemplate::e(number_format($totalFine, 2)) ?><br>
    La multa aumenta $<?= MailTemplate::e($finePerDay) ?> por cada día adicional de retraso.
</div>

<p>Por favor, devuelva el material a la brevedad posible.</p>
<p style="text-align:center;">
    <a href="<?= MailTemplate::e($libraryUrl) ?>" class="btn">Ver mis préstamos</a>
</p>
<?php $content = ob_get_clean();
require BASE_PATH . '/views/emails/layout.html.php';
```

```php
<!-- views/emails/welcome.html.php -->
<?php ob_start(); ?>
<h2>¡Bienvenido/a a la Biblioteca!</h2>
<p>Hola <strong><?= MailTemplate::e($userName) ?></strong>,</p>
<p>Su cuenta ha sido creada exitosamente. A partir de ahora puede:</p>
<ul>
    <li>Buscar y reservar libros en línea</li>
    <li>Consultar sus préstamos activos</li>
    <li>Recibir notificaciones de vencimiento</li>
</ul>
<p><strong>Su número de socio:</strong> <?= MailTemplate::e($memberNumber) ?></p>
<p style="text-align:center;">
    <a href="<?= MailTemplate::e($loginUrl) ?>" class="btn">Acceder a mi cuenta</a>
</p>
<?php $content = ob_get_clean();
require BASE_PATH . '/views/emails/layout.html.php';
```

```php
<!-- views/emails/password_reset.html.php -->
<?php ob_start(); ?>
<h2>Restablecer contraseña</h2>
<p>Hola <strong><?= MailTemplate::e($userName) ?></strong>,</p>
<p>Recibimos una solicitud para restablecer la contraseña de su cuenta.
   Este enlace es válido por <strong>60 minutos</strong>:</p>
<p style="text-align:center;">
    <a href="<?= MailTemplate::e($resetUrl) ?>" class="btn">Restablecer contraseña</a>
</p>
<div class="alert">
    Si no solicitó este cambio, ignore este correo. Su contraseña no será modificada.
</div>
<?php $content = ob_get_clean();
require BASE_PATH . '/views/emails/layout.html.php';
```

---

## Email Queue (Database)

```sql
-- Database queue table
CREATE TABLE email_queue (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `to`         VARCHAR(500)  NOT NULL,        -- JSON: {"email":"name"}
    subject      VARCHAR(255)  NOT NULL,
    template     VARCHAR(100)  NOT NULL,
    data         JSON          NOT NULL,
    status       ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
    attempts     TINYINT       NOT NULL DEFAULT 0,
    max_attempts TINYINT       NOT NULL DEFAULT 3,
    error        TEXT          NULL,
    available_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at      DATETIME      NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_email_queue_status_available (status, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```php
<?php
// src/Mail/MailQueue.php
declare(strict_types=1);

namespace Mail;

use Core\Database;

final class MailQueue
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /** Push an email job to the queue */
    public function push(
        array  $to,
        string $subject,
        string $template,
        array  $data = [],
        int    $delaySeconds = 0,
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO email_queue (`to`, subject, template, data, available_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            json_encode($to),
            $subject,
            $template,
            json_encode($data),
            date('Y-m-d H:i:s', time() + $delaySeconds),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Process pending jobs — called by cron every minute */
    public function process(int $batchSize = 20): void
    {
        $mailer = new \Services\MailService();

        $jobs = $this->db->query("
            SELECT * FROM email_queue
            WHERE status = 'pending'
              AND available_at <= NOW()
              AND attempts < max_attempts
            ORDER BY available_at ASC
            LIMIT {$batchSize}
            FOR UPDATE SKIP LOCKED
        ")->fetchAll();

        foreach ($jobs as $job) {
            $this->processJob($mailer, $job);
        }
    }

    private function processJob(\Services\MailService $mailer, array $job): void
    {
        // Mark as processing
        $this->db->prepare("UPDATE email_queue SET status='processing', attempts=attempts+1 WHERE id=?")
                 ->execute([$job['id']]);

        try {
            $to   = json_decode($job['to'],   true);
            $data = json_decode($job['data'], true);

            $template = MailTemplate::render($job['template'], $data);

            $sent = $mailer->send($to, $job['subject'], $template['html'], $template['text']);

            if ($sent) {
                $this->db->prepare("UPDATE email_queue SET status='sent', sent_at=NOW() WHERE id=?")
                         ->execute([$job['id']]);
            } else {
                throw new \RuntimeException('MailService::send() returned false');
            }

        } catch (\Throwable $e) {
            $status = $job['attempts'] + 1 >= $job['max_attempts'] ? 'failed' : 'pending';
            $this->db->prepare("UPDATE email_queue SET status=?, error=? WHERE id=?")
                     ->execute([$status, $e->getMessage(), $job['id']]);
        }
    }

    /** Retry failed jobs */
    public function retryFailed(): int
    {
        return $this->db->exec("
            UPDATE email_queue
            SET status='pending', attempts=0, error=NULL
            WHERE status='failed'
              AND attempts < max_attempts
        ");
    }
}
```

---

## Library-Specific Notification Service

```php
<?php
// src/Services/NotificationService.php
declare(strict_types=1);

namespace Services;

use Mail\MailQueue;

final class NotificationService
{
    public function __construct(private readonly MailQueue $queue) {}

    public function sendWelcome(array $user): void
    {
        $this->queue->push(
            to:       [$user['email'] => $user['name']],
            subject:  'Bienvenido/a a la Biblioteca',
            template: 'welcome',
            data:     [
                'userName'     => $user['name'],
                'memberNumber' => $user['member_number'],
                'loginUrl'     => ($_ENV['APP_URL'] ?? '') . '/login',
            ],
        );
    }

    public function sendPasswordReset(array $user, string $resetUrl): void
    {
        $this->queue->push(
            to:       [$user['email'] => $user['name']],
            subject:  'Restablecimiento de contraseña',
            template: 'password_reset',
            data:     [
                'userName' => $user['name'],
                'resetUrl' => $resetUrl,
            ],
        );
    }

    public function sendLoanConfirmation(array $user, array $loan): void
    {
        $this->queue->push(
            to:       [$user['email'] => $user['name']],
            subject:  'Confirmación de préstamo — ' . $loan['book_title'],
            template: 'loan_confirmation',
            data:     [
                'userName'  => $user['name'],
                'loan'      => $loan,
                'returnUrl' => ($_ENV['APP_URL'] ?? '') . '/my-loans',
            ],
        );
    }

    /** Call from cron — sends overdue notices for all members */
    public function sendOverdueNotices(array $overdueByUser): void
    {
        foreach ($overdueByUser as $userId => $data) {
            $user  = $data['user'];
            $loans = $data['loans'];

            $totalFine = array_sum(array_column($loans, 'fine_amount'));

            $this->queue->push(
                to:       [$user['email'] => $user['name']],
                subject:  'Préstamo vencido — Por favor devuelva el material',
                template: 'loan_overdue',
                data:     [
                    'userName'   => $user['name'],
                    'loans'      => $loans,
                    'totalFine'  => $totalFine,
                    'finePerDay' => $_ENV['FINE_PER_DAY'] ?? '0.50',
                    'libraryUrl' => ($_ENV['APP_URL'] ?? '') . '/my-loans',
                ],
            );
        }
    }

    public function sendReservationAvailable(array $user, array $book): void
    {
        $this->queue->push(
            to:       [$user['email'] => $user['name']],
            subject:  'Libro disponible: ' . $book['title'],
            template: 'reservation_available',
            data:     [
                'userName'   => $user['name'],
                'bookTitle'  => $book['title'],
                'bookAuthor' => $book['author'],
                'pickupDays' => 3,
                'reserveUrl' => ($_ENV['APP_URL'] ?? '') . '/books/' . $book['id'],
            ],
            delaySeconds: 0,
        );
    }
}
```

---

## Cron Workers

```php
<?php
// bin/mail-worker.php — runs every minute via cron
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$queue = new \Mail\MailQueue();
$queue->process(batchSize: 20);
echo date('Y-m-d H:i:s') . " — Mail worker completed.\n";
```

```php
<?php
// bin/notify-overdue.php — runs daily at 8am via cron
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$loanRepo    = new \Repositories\LoanRepository();
$notifier    = new \Services\NotificationService(new \Mail\MailQueue());

$overdueLoans = $loanRepo->findOverdueGroupedByUser();
$notifier->sendOverdueNotices($overdueLoans);

echo date('Y-m-d H:i:s') . " — Overdue notices queued for " . count($overdueLoans) . " users.\n";
```

---

## SMTP Configuration (.env)

```dotenv
# SMTP settings
MAIL_DRIVER=smtp                    # smtp | mail (PHP native)
MAIL_HOST=smtp.example.com
MAIL_PORT=587                       # 587=TLS | 465=SSL | 25=plain
MAIL_USERNAME=no-reply@biblioteca.com
MAIL_PASSWORD=strong_mail_password
MAIL_FROM_ADDRESS=no-reply@biblioteca.com
MAIL_FROM_NAME="Biblioteca Municipal"
MAIL_ENCRYPTION=tls                 # tls | ssl | none

# Library settings
FINE_PER_DAY=0.50
APP_URL=https://biblioteca.example.com
```

---

## SPF / DKIM / DMARC (Deliverability)

```dns
; Add to DNS zone of your domain

; SPF — authorize your mail server
biblioteca.example.com. TXT "v=spf1 mx a:mail.example.com include:_spf.google.com ~all"

; DMARC — policy for failed authentication
_dmarc.biblioteca.example.com. TXT "v=DMARC1; p=quarantine; rua=mailto:dmarc@biblioteca.example.com; pct=100"
```

```bash
# Generate DKIM key pair
openssl genrsa -out dkim-private.pem 2048
openssl rsa -in dkim-private.pem -pubout -out dkim-public.pem

# Add public key to DNS:
# mail._domainkey.biblioteca.example.com. TXT "v=DKIM1; k=rsa; p=<base64-public-key>"
```

---

## Development: Log Emails Instead of Sending

```php
<?php
// src/Mail/LogMailer.php — use in development/testing
declare(strict_types=1);

namespace Mail;

final class LogMailer
{
    public static function intercept(string $template, array $data, array $to, string $subject): void
    {
        $dir  = BASE_PATH . '/storage/mail-preview';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file = $dir . '/' . date('Ymd_His') . '_' . $template . '.html';
        $rendered = MailTemplate::render($template, $data);

        file_put_contents($file, "<!-- To: " . json_encode($to) . " Subject: {$subject} -->\n" . $rendered['html']);
        error_log("Mail intercepted → {$file}");
    }
}
```

---

## Workflow

1. **Nunca enviar en el request** — Siempre `MailQueue::push()` en el controller; el cron procesa el envío.
2. **HTML + texto plano siempre** — Definir ambas versiones; mejora deliverability y accesibilidad.
3. **Estilos inline en emails** — Los clientes de email no soportan `<link>` ni `<style>` externos.
4. **Validar email antes de encolar** — `filter_var($email, FILTER_VALIDATE_EMAIL)` antes de `push()`.
5. **Reintentos con backoff** — `max_attempts = 3`; el worker no reintenta infinitamente.
6. **Log de auditoría siempre** — Cada email enviado o fallido queda registrado en `storage/logs/mail.log`.
7. **DKIM + SPF + DMARC** — Sin estos tres, los emails van a spam. Configurar antes del primer envío en producción.
8. **Interceptar en desarrollo** — Usar `LogMailer` en `APP_ENV=local`; nunca enviar emails reales al desarrollar.
