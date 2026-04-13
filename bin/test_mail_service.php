<?php
// bin/test_mail_service.php — Prueba MailService::send() con SMTP real.
// Uso: php bin/test_mail_service.php [destino@ejemplo.com]
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

use Services\MailService;

$to = $argv[1] ?? 'info@softecsa.com';

$mailer = new MailService();

if (!$mailer->isSmtpConfigured()) {
    fwrite(STDERR, $mailer->smtpWarningMessage() . "\n");
    exit(2);
}

echo "Enviando correo de prueba a {$to} …\n";

$mailer->send(
    toEmail:  $to,
    toName:   'Test',
    subject:  'Prueba MailService — Biblioteca',
    bodyHtml: '<h2>Correo de prueba</h2><p>El servicio SMTP de la biblioteca está funcionando correctamente.</p>',
    bodyText: "Correo de prueba\nEl servicio SMTP de la biblioteca está funcionando correctamente.",
);

echo "✓ Correo enviado correctamente a {$to}\n";
