<?php
// bin/test_smtp.php  — Envía un correo de prueba usando las credenciales SMTP de system_settings.
// Uso: php bin/test_smtp.php to@example.com
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/bootstrap.php';

$to = $argv[1] ?? 'info@softecsa.com';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Dirección de destino inválida: {$to}\n");
    exit(1);
}

// ── Cargar config SMTP desde system_settings ─────────────────────────────────
$cfg = require BASE_PATH . '/config/mail.php';

$host       = trim((string) ($cfg['host'] ?? ''));
$port       = (int) ($cfg['port'] ?? 587);
$username   = trim((string) ($cfg['username'] ?? ''));
$password   = (string) ($cfg['password'] ?? '');
$encryption = strtolower(trim((string) ($cfg['encryption'] ?? 'tls')));
$fromAddr   = trim((string) ($cfg['from_address'] ?? ''));
$fromName   = trim((string) ($cfg['from_name'] ?? 'Biblioteca'));
$timeout    = max(5, (int) ($cfg['timeout'] ?? 30));

$missing = [];
if ($host === '')      { $missing[] = 'smtp_host'; }
if ($port <= 0)        { $missing[] = 'smtp_port'; }
if ($username === '')  { $missing[] = 'smtp_username'; }
if ($password === '')  { $missing[] = 'smtp_password'; }
if ($fromAddr === '')  { $missing[] = 'smtp_from_address'; }

if ($missing !== []) {
    fwrite(STDERR, "SMTP incompleto. Faltan: " . implode(', ', $missing) . "\n");
    fwrite(STDERR, "Configura en Admin > Settings > Correo SMTP.\n");
    exit(2);
}

echo "Config SMTP cargada:\n";
echo "  Host:     {$host}:{$port}\n";
echo "  Usuario:  {$username}\n";
echo "  Cifrado:  {$encryption}\n";
echo "  Remitente:{$fromAddr}\n";
echo "  Destino:  {$to}\n\n";

// ── Helper: leer línea del socket ─────────────────────────────────────────────
$readLine = static function ($socket): string {
    $line = '';
    while (!feof($socket)) {
        $chunk = fgets($socket, 1024);
        if ($chunk === false) {
            break;
        }
        $line .= $chunk;
        // La respuesta de SMTP termina cuando el 4.º car. no es '-'  (RFC 5321)
        if (strlen($line) >= 4 && $line[3] !== '-') {
            break;
        }
    }
    return rtrim($line);
};

$cmd = static function ($socket, string $command, $readLine): string {
    fwrite($socket, $command . "\r\n");
    return $readLine($socket);
};

// ── Conectar ──────────────────────────────────────────────────────────────────
$errNo  = 0;
$errStr = '';

if ($encryption === 'ssl') {
    $socketAddr = "ssl://{$host}:{$port}";
} else {
    $socketAddr = "tcp://{$host}:{$port}";
}

echo "Conectando a {$socketAddr}…\n";
$socket = @stream_socket_client($socketAddr, $errNo, $errStr, $timeout);

if ($socket === false) {
    fwrite(STDERR, "No se pudo conectar al servidor SMTP: [{$errNo}] {$errStr}\n");
    exit(3);
}

stream_set_timeout($socket, $timeout);

// ── Handshake ─────────────────────────────────────────────────────────────────
$banner = $readLine($socket);
echo "  <- {$banner}\n";

$resp = $cmd($socket, "EHLO biblioteca.local", $readLine);
echo "  <- {$resp}\n";

// ── STARTTLS si TLS ───────────────────────────────────────────────────────────
if ($encryption === 'tls') {
    $resp = $cmd($socket, 'STARTTLS', $readLine);
    echo "  <- {$resp}\n";
    if (!str_starts_with(ltrim($resp), '220')) {
        fwrite(STDERR, "STARTTLS rechazado: {$resp}\n");
        fclose($socket);
        exit(4);
    }
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $resp = $cmd($socket, "EHLO biblioteca.local", $readLine);
    echo "  <- {$resp}\n";
}

// ── AUTH LOGIN ────────────────────────────────────────────────────────────────
$resp = $cmd($socket, 'AUTH LOGIN', $readLine);
echo "  <- {$resp}\n";
$resp = $cmd($socket, base64_encode($username), $readLine);
echo "  <- {$resp}\n";
$resp = $cmd($socket, base64_encode($password), $readLine);
echo "  <- {$resp}\n";

if (!str_starts_with(ltrim($resp), '235')) {
    fwrite(STDERR, "Autenticación fallida: {$resp}\n");
    $cmd($socket, 'QUIT', $readLine);
    fclose($socket);
    exit(5);
}

// ── Enviar ────────────────────────────────────────────────────────────────────
$date    = date('r');
$msgId   = '<test.' . time() . '@biblioteca.local>';
$subject = '=?UTF-8?B?' . base64_encode('Prueba SMTP — Biblioteca') . '?=';
$body    = "Este es un correo de prueba enviado desde el sistema de Biblioteca.\r\n"
         . "Fecha/hora: {$date}\r\n";

$resp = $cmd($socket, "MAIL FROM:<{$fromAddr}>", $readLine);
echo "  <- {$resp}\n";
$resp = $cmd($socket, "RCPT TO:<{$to}>", $readLine);
echo "  <- {$resp}\n";
$resp = $cmd($socket, 'DATA', $readLine);
echo "  <- {$resp}\n";

$email  = "Date: {$date}\r\n";
$email .= "From: {$fromName} <{$fromAddr}>\r\n";
$email .= "To: {$to}\r\n";
$email .= "Subject: {$subject}\r\n";
$email .= "Message-ID: {$msgId}\r\n";
$email .= "MIME-Version: 1.0\r\n";
$email .= "Content-Type: text/plain; charset=UTF-8\r\n";
$email .= "Content-Transfer-Encoding: 8bit\r\n";
$email .= "\r\n";
$email .= $body;
$email .= "\r\n.";

$resp = $cmd($socket, $email, $readLine);
echo "  <- {$resp}\n";

$cmd($socket, 'QUIT', $readLine);
fclose($socket);

if (str_starts_with(ltrim($resp), '250')) {
    echo "\n✓ Correo enviado correctamente a {$to}\n";
    exit(0);
} else {
    fwrite(STDERR, "\n✗ El servidor no aceptó el mensaje: {$resp}\n");
    exit(6);
}
