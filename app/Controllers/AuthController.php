<?php
// app/Controllers/AuthController.php
declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Core\Database;
use Helpers\EcuadorId;
use Middleware\CsrfMiddleware;
use Repositories\UserRepository;
use Services\EmailTemplateService;
use Services\MailService;
use Services\MailQueueService;

final class AuthController
{
    private View           $view;
    private UserRepository $users;
    private MailQueueService $mailQueue;
    private EmailTemplateService $emailTemplate;

    private const REMEMBER_DAYS   = 30;
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_SECONDS = 900; // 15 min
    private const RESET_TTL_MIN   = 60;

    public function __construct()
    {
        $this->view  = new View(BASE_PATH . '/views');
        $this->users = new UserRepository();
        $this->mailQueue = new MailQueueService();
        $this->emailTemplate = new EmailTemplateService();
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function showLogin(Request $request): Response
    {
        if (Session::has('auth.user_id')) {
            return $this->redirect($this->landingPathForRole((string) Session::get('auth.role', 'user')));
        }

        return Response::html($this->view->render('auth/login', [
            'title'    => 'Iniciar sesión — Biblioteca',
            'csrf'     => CsrfMiddleware::token(),
            'redirect' => $request->get('redirect', ''),
        ], 'layouts/auth'));
    }

    public function login(Request $request): Response
    {
        $email    = mb_strtolower(trim((string) $request->post('email', '')));
        $password = (string) $request->post('password', '');
        $remember = (bool) $request->post('remember', false);
        $redirect = trim((string) $request->post('redirect', ''));

        // CSRF already validated by middleware

        if ($email === '' || $password === '') {
            Session::flash('error', 'Correo y contraseña son requeridos.');
            return $this->redirect('/login');
        }

        $ip = $request->ip();

        if ($this->isLockedOut($email, $ip)) {
            Session::flash('error', 'Demasiados intentos fallidos. Espera 15 minutos e intenta de nuevo.');
            return $this->redirect('/login');
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->recordFailedAttempt($email, $ip);
            Session::flash('error', 'Correo o contraseña incorrectos.');
            return $this->redirect('/login');
        }

        if (($user['role'] ?? 'user') === 'user'
            && ($user['status'] ?? 'inactive') === 'inactive'
            && empty($user['email_verified_at'])) {
            Session::flash('error', 'Debes verificar tu correo electrónico antes de iniciar sesión. Revisa tu bandeja de entrada.');
            return $this->redirect('/login');
        }

        if ($user['status'] !== 'active') {
            Session::flash('error', 'Tu cuenta está ' . ($user['status'] === 'suspended' ? 'suspendida' : 'inactiva') . '. Contacta al administrador.');
            return $this->redirect('/login');
        }

        $this->clearFailedAttempts($email, $ip);
        $this->createSession($user);

        if ($remember) {
            $this->createRememberToken((int) $user['id']);
        }

        $this->users->update((int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);

        $this->auditLog('login_success', (int) $user['id']);

        // Safe redirect (only allow internal paths)
        $target = BASE_URL . $this->landingPathForRole((string) ($user['role'] ?? 'user'));
        if ($redirect !== '' && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
            $target = BASE_URL . $redirect;
        }

        return Response::redirect($target);
    }

    public function logout(Request $request): Response
    {
        $userId = Session::get('auth.user_id');
        if ($userId) {
            $this->auditLog('logout', (int) $userId);
            $this->users->clearRememberToken((int) $userId);
        }

        setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
        Session::destroy();

        return $this->redirect('/login');
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function showRegister(Request $request): Response
    {
        if (Session::has('auth.user_id')) {
            return $this->redirect($this->landingPathForRole((string) Session::get('auth.role', 'user')));
        }

        return Response::html($this->view->render('auth/register', [
            'title' => 'Crear cuenta — Biblioteca',
            'csrf'  => CsrfMiddleware::token(),
            'old'   => [],
        ], 'layouts/auth'));
    }

    public function register(Request $request): Response
    {
        $name           = trim((string) $request->post('name', ''));
        $documentNumber = EcuadorId::normalizeCedula((string) $request->post('document_number', ''));
        $email          = mb_strtolower(trim((string) $request->post('email', '')));
        $password       = (string) $request->post('password', '');
        $confirm        = (string) $request->post('password_confirmation', '');

        $errors = [];

        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre debe tener al menos 3 caracteres.';
        }
        if ($documentNumber === '') {
            $errors[] = 'La cédula es obligatoria.';
        } elseif (!EcuadorId::isValidCedula($documentNumber)) {
            $errors[] = 'La cédula ecuatoriana no es válida.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!hash_equals($password, $confirm)) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('old_name', $name);
            Session::flash('old_email', $email);
            Session::flash('old_document_number', $documentNumber);
            return $this->redirect('/register');
        }

        if ($this->users->emailExists($email)) {
            Session::flash('error', 'Ya existe una cuenta con ese correo electrónico.');
            Session::flash('old_name', $name);
            Session::flash('old_document_number', $documentNumber);
            return $this->redirect('/register');
        }

        if ($this->users->documentExists($documentNumber)) {
            Session::flash('error', 'Ya existe una cuenta con esa cédula.');
            Session::flash('old_name', $name);
            Session::flash('old_email', $email);
            return $this->redirect('/register');
        }

        $userNumber = $this->users->generateUserNumber();

        try {
            $userId = $this->users->create([
                'user_number' => $userNumber,
                'document_number'   => $documentNumber,
                'name'              => $name,
                'email'             => $email,
                'password_hash'     => password_hash($password, PASSWORD_ARGON2ID),
                'role'              => 'user',
                'user_type'       => 'student',
                'status'            => 'inactive',
            ]);
        } catch (\PDOException $e) {
            $msg = mb_strtolower($e->getMessage());
            if (str_contains($msg, 'uq_users_document')) {
                Session::flash('error', 'Ya existe una cuenta con esa cédula.');
            } elseif (str_contains($msg, 'uq_users_email')) {
                Session::flash('error', 'Ya existe una cuenta con ese correo electrónico.');
            } else {
                Session::flash('error', 'No se pudo completar el registro. Inténtalo nuevamente.');
            }
            Session::flash('old_name', $name);
            Session::flash('old_email', $email);
            Session::flash('old_document_number', $documentNumber);
            return $this->redirect('/register');
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->users->saveEmailVerificationToken($userId, hash('sha256', $token), $expires);

        $verifyUrl = $this->appBaseUrl() . '/verify-email/' . $token . '?email=' . urlencode($email);
        $this->queueVerificationEmail($name, $email, $verifyUrl);

        $this->auditLog('register', $userId, ['verification_email' => $email]);

        Session::flash('success', 'Registro completado. Revisa tu correo para activar tu cuenta antes de iniciar sesión.');
        return $this->redirect('/login');
    }

    public function verifyEmail(Request $request, string $token = ''): Response
    {
        $email = mb_strtolower(trim((string) $request->get('email', '')));

        if ($token === '') {
            Session::flash('error', 'El enlace de verificación no es válido.');
            return $this->redirect('/login');
        }

        $tokenHash = hash('sha256', $token);

        $record = null;
        $user = null;

        if ($email !== '') {
            $user = $this->users->findByEmail($email);
            if ($user === null || (string) ($user['role'] ?? '') === 'admin') {
                Session::flash('error', 'No se pudo verificar la cuenta.');
                return $this->redirect('/login');
            }

            $record = $this->users->findEmailVerificationToken((int) $user['id'], $tokenHash);
        } else {
            $record = $this->users->findPendingEmailVerificationByToken($tokenHash);
            if ($record !== null) {
                $user = $this->users->findById((int) $record['user_id']);
            }
        }

        if ($user === null || (string) ($user['role'] ?? '') === 'admin') {
            Session::flash('error', 'No se pudo verificar la cuenta.');
            return $this->redirect('/login');
        }

        if (!empty($user['email_verified_at'])) {
            Session::flash('success', 'Tu correo ya estaba verificado. Ya puedes iniciar sesión.');
            return $this->redirect('/login');
        }

        if ($record === null || strtotime((string) $record['expires_at']) < time()) {
            Session::flash('error', 'El enlace de verificación es inválido o expiró.');
            return $this->redirect('/login');
        }

        $this->users->update((int) $user['id'], [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
        ]);
        $this->users->markEmailVerificationUsed((int) $user['id']);
        $this->auditLog('email_verified', (int) $user['id']);

        Session::flash('success', 'Correo verificado correctamente. Ya puedes iniciar sesión.');
        return $this->redirect('/login');
    }

    // ── Forgot password ───────────────────────────────────────────────────────

    public function showForgotPassword(Request $request): Response
    {
        return Response::html($this->view->render('auth/forgot-password', [
            'title' => 'Recuperar contraseña — Biblioteca',
            'csrf'  => CsrfMiddleware::token(),
        ], 'layouts/auth'));
    }

    public function forgotPassword(Request $request): Response
    {
        $email = mb_strtolower(trim((string) $request->post('email', '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Ingresa un correo electrónico válido.');
            return $this->redirect('/forgot-password');
        }

        // Always show success to prevent email enumeration
        $user = $this->users->findByEmail($email);

        if ($user !== null && $user['status'] === 'active') {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TTL_MIN . ' minutes'));
            $this->users->savePasswordResetToken((int) $user['id'], hash('sha256', $token), $expires);

            $resetUrl = BASE_URL . '/reset-password/' . $token . '?email=' . urlencode($email);

            // Write reset link to log (replace with real email service when available)
            $this->auditLog('password_reset_requested', (int) $user['id'], ['reset_url' => $resetUrl]);
        }

        Session::flash('success', 'Si el correo existe en nuestro sistema, recibirás las instrucciones en breve.');
        return $this->redirect('/forgot-password');
    }

    // ── Reset password ────────────────────────────────────────────────────────

    public function showResetPassword(Request $request, string $token = ''): Response
    {
        $email = trim((string) $request->get('email', ''));

        if ($token === '' || $email === '') {
            Session::flash('error', 'El enlace de restablecimiento no es válido.');
            return $this->redirect('/forgot-password');
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            Session::flash('error', 'El enlace de restablecimiento no es válido.');
            return $this->redirect('/forgot-password');
        }

        $record = $this->users->findPasswordResetToken((int) $user['id'], hash('sha256', $token));
        if ($record === null || strtotime($record['expires_at']) < time()) {
            Session::flash('error', 'El enlace ha expirado. Solicita uno nuevo.');
            return $this->redirect('/forgot-password');
        }

        return Response::html($this->view->render('auth/reset-password', [
            'title' => 'Nueva contraseña — Biblioteca',
            'csrf'  => CsrfMiddleware::token(),
            'token' => $token,
            'email' => $email,
        ], 'layouts/auth'));
    }

    public function resetPassword(Request $request): Response
    {
        $email    = mb_strtolower(trim((string) $request->post('email', '')));
        $token    = (string) $request->post('token', '');
        $password = (string) $request->post('password', '');
        $confirm  = (string) $request->post('password_confirmation', '');

        if ($token === '' || $email === '') {
            Session::flash('error', 'Solicitud inválida.');
            return $this->redirect('/forgot-password');
        }

        if (mb_strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            return Response::redirect(BASE_URL . '/reset-password/' . $token . '?email=' . urlencode($email));
        }

        if (!hash_equals($password, $confirm)) {
            Session::flash('error', 'Las contraseñas no coinciden.');
            return Response::redirect(BASE_URL . '/reset-password/' . $token . '?email=' . urlencode($email));
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            Session::flash('error', 'Solicitud inválida.');
            return $this->redirect('/forgot-password');
        }

        $record = $this->users->findPasswordResetToken((int) $user['id'], hash('sha256', $token));
        if ($record === null || strtotime($record['expires_at']) < time()) {
            Session::flash('error', 'El enlace ha expirado. Solicita uno nuevo.');
            return $this->redirect('/forgot-password');
        }

        $this->users->update((int) $user['id'], [
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
        ]);
        $this->users->markPasswordResetUsed((int) $user['id']);
        $this->users->clearRememberToken((int) $user['id']);

        $this->auditLog('password_reset_completed', (int) $user['id']);

        Session::flash('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
        return $this->redirect('/login');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function redirect(string $path): Response
    {
        return Response::redirect(BASE_URL . $path);
    }

    private function landingPathForRole(string $role): string
    {
        return match ($role) {
            'admin', 'librarian' => '/admin',
            'teacher' => '/teacher',
            default => '/account',
        };
    }

    private function createSession(array $user): void
    {
        Session::regenerate();
        Session::set('auth.user_id', $user['id']);
        Session::set('auth.role',    $user['role']);
        Session::set('auth.name',    $user['name']);
        Session::set('auth.email',   $user['email']);
    }

    private function createRememberToken(int $userId): void
    {
        $token   = bin2hex(random_bytes(32));
        $hash    = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::REMEMBER_DAYS . ' days'));

        $this->users->saveRememberToken($userId, $hash, $expires);

        setcookie('remember_token', $token, [
            'expires'  => strtotime('+' . self::REMEMBER_DAYS . ' days'),
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function isLockedOut(string $email, string $ip): bool
    {
        $data = $this->throttleRead($email, $ip);
        if ($data === null) return false;
        return $data['attempts'] >= self::MAX_ATTEMPTS
            && $data['last_attempt'] > time() - self::LOCKOUT_SECONDS;
    }

    private function recordFailedAttempt(string $email, string $ip): void
    {
        $data = $this->throttleRead($email, $ip) ?? ['attempts' => 0, 'last_attempt' => time()];
        $data['attempts']++;
        $data['last_attempt'] = time();
        $this->throttleWrite($email, $ip, $data);
        $this->auditLog('login_failed', null, ['email' => $email, 'ip' => $ip]);
    }

    private function clearFailedAttempts(string $email, string $ip): void
    {
        $file = $this->throttleFile($email, $ip);
        if (file_exists($file)) @unlink($file);
    }

    private function throttleFile(string $email, string $ip): string
    {
        $dir = BASE_PATH . '/storage/throttle';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir . '/' . hash('sha256', $email . $ip) . '.json';
    }

    private function throttleRead(string $email, string $ip): ?array
    {
        $file = $this->throttleFile($email, $ip);
        if (!file_exists($file)) return null;
        // Auto-expire
        if (filemtime($file) + self::LOCKOUT_SECONDS < time()) {
            @unlink($file);
            return null;
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function throttleWrite(string $email, string $ip, array $data): void
    {
        file_put_contents($this->throttleFile($email, $ip), json_encode($data), LOCK_EX);
    }

    private function auditLog(string $action, ?int $userId, array $context = []): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $line = sprintf(
            "[%s] action=%s user_id=%s ip=%s %s\n",
            date('Y-m-d H:i:s'),
            $action,
            $userId ?? 'null',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $context ? json_encode($context) : ''
        );
        file_put_contents($dir . '/auth.log', $line, FILE_APPEND | LOCK_EX);
    }

    private function queueVerificationEmail(string $name, string $email, string $verifyUrl): void
    {
        $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $contentHtml = '<p>Hola <strong>' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>,</p>'
            . '<p>Para activar tu cuenta, verifica tu correo haciendo clic en el siguiente enlace:</p>'
            . '<p style="margin:18px 0;"><a href="' . $safeUrl . '" '
            . 'style="display:inline-block;background:#1e3a8a;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;">'
            . 'Verificar mi correo</a></p>'
            . '<p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>'
            . '<p style="word-break:break-all;">' . $safeUrl . '</p>';

        $html = $this->emailTemplate->renderSystem(
            'Activa tu cuenta de Biblioteca',
            'Verificación de correo',
            'Tu cuenta fue creada correctamente. Falta un último paso para activarla.',
            $contentHtml,
            'Este enlace expira en 24 horas.'
        );

        $text = $this->emailTemplate->renderSystemText(
            'Verificación de correo',
            'Tu cuenta fue creada correctamente. Falta un último paso para activarla.',
            'Abre este enlace para verificar tu correo: ' . $verifyUrl,
            'Este enlace expira en 24 horas.'
        );

        $queueId = null;

        try {
            $queueId = $this->mailQueue->enqueue(
                $email,
                $name,
                'Verifica tu correo para activar tu cuenta',
                $html,
                $text,
                null,
                1  // priority: critical
            );

            // En entorno local/desarrollo se intenta envío inmediato para evitar
            // depender de cron mientras se prueba el flujo de registro.
            if ($this->shouldSendVerificationImmediately()) {
                $mailer = new MailService();
                $mailer->send(
                    toEmail: $email,
                    toName: $name,
                    subject: 'Verifica tu correo para activar tu cuenta',
                    bodyHtml: $html,
                    bodyText: $text,
                    context: [
                        'source' => 'register_verify_immediate',
                        'queue_id' => $queueId,
                    ]
                );
                $this->mailQueue->markSent($queueId);
            }
        } catch (\Throwable $e) {
            if (is_int($queueId) && $queueId > 0) {
                try {
                    $this->mailQueue->markRetry($queueId, 1, $e->getMessage(), 5);
                } catch (\Throwable) {
                    // Ignore retry-mark failures to preserve original exception context in logs.
                }
            }
            $this->auditLog('verification_email_queue_failed', null, [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldSendVerificationImmediately(): bool
    {
        $env = mb_strtolower((string) ($_ENV['APP_ENV'] ?? 'production'));
        return $env !== 'production';
    }

    private function appBaseUrl(): string
    {
        $basePath = rtrim((string) (parse_url(BASE_URL, PHP_URL_PATH) ?? BASE_URL), '/');
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        // 1) Prefer Admin setting: app_url
        $configured = '';
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare('SELECT value FROM system_settings WHERE `key` = ? LIMIT 1');
            $stmt->execute(['app_url']);
            $configured = rtrim((string) ($stmt->fetchColumn() ?: ''), '/');
        } catch (\Throwable) {
            $configured = '';
        }

        // 2) Fallback to APP_URL from config/.env if app_url setting is empty
        if ($configured === '') {
            $config = require BASE_PATH . '/config/app.php';
            $configured = rtrim((string) ($config['url'] ?? ''), '/');
        }

        // 3) If still empty, detect from current request host + scheme
        if ($configured === '') {
            $configured = $this->detectRuntimeOrigin();
        }

        $configuredPath = rtrim((string) (parse_url($configured, PHP_URL_PATH) ?? ''), '/');
        if ($basePath !== '' && $configuredPath !== '' && $configuredPath === $basePath) {
            return $configured;
        }

        if ($basePath !== '' && !str_ends_with($configured, $basePath)) {
            return $configured . $basePath;
        }

        return $configured;
    }

    private function detectRuntimeOrigin(): string
    {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['SERVER_PORT'] ?? '') === '443';
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto === 'https') {
            $https = true;
        }

        $scheme = $https ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }

        return $scheme . '://' . $host;
    }
}

