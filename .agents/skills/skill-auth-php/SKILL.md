---
name: skill-auth-php
description: "**WORKFLOW SKILL** — Authentication, authorization and security in pure PHP at expert level. USE FOR: session-based authentication; password hashing with password_hash/password_verify; CSRF token generation and validation; role-based access control (RBAC) for library roles (Admin, Librarian, Member, Guest); permission system; login/logout/remember-me; brute force protection and login rate limiting; secure session management; XSS and SQL injection prevention; HTTP security headers; input sanitization; JWT tokens for API; account lockout; audit logging; password reset flow with secure tokens; two-factor authentication (2FA) with TOTP. DO NOT USE FOR: OAuth2 third-party providers (outside scope); framework-specific auth systems."
---

# Auth PHP — Professional Authentication & Security

## Core Philosophy

- **Passwords never stored plain**: Always `password_hash()` with `PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`.
- **Defense in depth**: Validate at every layer — input, session, permission, query.
- **Sessions over JWT for web**: JWT only for stateless APIs; sessions for browser-based UI.
- **Fail closed**: Deny by default; grant explicitly.
- **Audit everything sensitive**: Login attempts, permission denials, password changes.

---

## Role & Permission System

```php
<?php
// src/Auth/Roles.php
declare(strict_types=1);

namespace Auth;

enum Role: string
{
    case Admin      = 'admin';
    case Librarian  = 'librarian';
    case Member     = 'member';
    case Guest      = 'guest';
}

enum Permission: string
{
    // Books
    case BooksView    = 'books.view';
    case BooksCreate  = 'books.create';
    case BooksEdit    = 'books.edit';
    case BooksDelete  = 'books.delete';

    // Loans
    case LoansView    = 'loans.view';
    case LoansCreate  = 'loans.create';
    case LoansReturn  = 'loans.return';
    case LoansManage  = 'loans.manage';

    // Users
    case UsersView    = 'users.view';
    case UsersCreate  = 'users.create';
    case UsersEdit    = 'users.edit';
    case UsersDelete  = 'users.delete';

    // Reports
    case ReportsView  = 'reports.view';
    case ReportsExport= 'reports.export';
}
```

```php
<?php
// src/Auth/AccessControl.php
declare(strict_types=1);

namespace Auth;

final class AccessControl
{
    /** Role → Permission map */
    private const ROLE_PERMISSIONS = [
        Role::Admin->value => [
            Permission::BooksView, Permission::BooksCreate, Permission::BooksEdit, Permission::BooksDelete,
            Permission::LoansView, Permission::LoansCreate, Permission::LoansReturn, Permission::LoansManage,
            Permission::UsersView, Permission::UsersCreate, Permission::UsersEdit, Permission::UsersDelete,
            Permission::ReportsView, Permission::ReportsExport,
        ],
        Role::Librarian->value => [
            Permission::BooksView, Permission::BooksCreate, Permission::BooksEdit,
            Permission::LoansView, Permission::LoansCreate, Permission::LoansReturn, Permission::LoansManage,
            Permission::UsersView,
            Permission::ReportsView,
        ],
        Role::Member->value => [
            Permission::BooksView,
            Permission::LoansView,
        ],
        Role::Guest->value => [
            Permission::BooksView,
        ],
    ];

    public static function can(string $role, Permission $permission): bool
    {
        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];
        return in_array($permission, $permissions, true);
    }

    public static function rolePermissions(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}
```

---

## Authentication Service

```php
<?php
// src/Auth/AuthService.php
declare(strict_types=1);

namespace Auth;

use Repositories\UserRepository;
use Helpers\Session;

final class AuthService
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_MINUTES = 15;
    private const REMEMBER_DAYS   = 30;

    public function __construct(private readonly UserRepository $users) {}

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->isLockedOut($email, $ip)) {
            throw new \RuntimeException('Cuenta bloqueada temporalmente. Intente en ' . self::LOCKOUT_MINUTES . ' minutos.');
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email, $ip);
            return false;
        }

        if ((int)($user['is_active'] ?? 1) === 0) {
            throw new \RuntimeException('Cuenta desactivada. Contacte al administrador.');
        }

        $this->clearFailedAttempts($email, $ip);
        $this->createSession($user);

        if ($remember) {
            $this->createRememberToken($user['id']);
        }

        $this->users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip]);
        $this->auditLog('login_success', $user['id']);

        return true;
    }

    public function logout(): void
    {
        $userId = Session::get('auth.user_id');
        if ($userId) {
            $this->auditLog('logout', (int) $userId);
            $this->clearRememberToken();
        }
        Session::destroy();
        session_regenerate_id(true);
    }

    public function check(): bool
    {
        if (Session::has('auth.user_id')) return true;
        return $this->checkRememberToken();
    }

    public function user(): ?array
    {
        if (!$this->check()) return null;
        return $this->users->findById((int) Session::get('auth.user_id'));
    }

    public function id(): ?int
    {
        return Session::get('auth.user_id') ? (int) Session::get('auth.user_id') : null;
    }

    public function role(): string
    {
        return Session::get('auth.role', Role::Guest->value);
    }

    public function can(Permission $permission): bool
    {
        return AccessControl::can($this->role(), $permission);
    }

    public function cannot(Permission $permission): bool
    {
        return !$this->can($permission);
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            Session::flash('error', 'Debe iniciar sesión para continuar.');
            \Core\Response::redirect('/login');
        }
    }

    public function requirePermission(Permission $permission): void
    {
        $this->requireAuth();
        if ($this->cannot($permission)) {
            $this->auditLog('permission_denied', $this->id(), ['permission' => $permission->value]);
            \Core\Response::abort(403, 'No tiene permisos para realizar esta acción.');
        }
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function createSession(array $user): void
    {
        session_regenerate_id(true);
        Session::set('auth.user_id', $user['id']);
        Session::set('auth.role',    $user['role']);
        Session::set('auth.email',   $user['email']);
    }

    private function createRememberToken(int $userId): void
    {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires   = date('Y-m-d H:i:s', strtotime('+' . self::REMEMBER_DAYS . ' days'));

        $this->users->saveRememberToken($userId, $tokenHash, $expires);

        setcookie('remember_token', $token, [
            'expires'  => strtotime('+' . self::REMEMBER_DAYS . ' days'),
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function checkRememberToken(): bool
    {
        $token = $_COOKIE['remember_token'] ?? '';
        if ($token === '') return false;

        $tokenHash = hash('sha256', $token);
        $user = $this->users->findByRememberToken($tokenHash);

        if ($user === null) return false;

        $this->createSession($user);
        return true;
    }

    private function clearRememberToken(): void
    {
        setcookie('remember_token', '', time() - 3600, '/');
        $userId = $this->id();
        if ($userId) $this->users->clearRememberToken($userId);
    }

    private function isLockedOut(string $email, string $ip): bool
    {
        $key  = $this->throttleKey($email, $ip);
        $data = apcu_fetch($key);
        if ($data === false) return false;
        return $data['attempts'] >= self::MAX_ATTEMPTS
            && $data['last_attempt'] > time() - (self::LOCKOUT_MINUTES * 60);
    }

    private function recordFailedAttempt(string $email, string $ip): void
    {
        $key  = $this->throttleKey($email, $ip);
        $data = apcu_fetch($key) ?: ['attempts' => 0, 'last_attempt' => time()];
        $data['attempts']++;
        $data['last_attempt'] = time();
        apcu_store($key, $data, self::LOCKOUT_MINUTES * 60);
        $this->auditLog('login_failed', null, ['email' => $email, 'ip' => $ip]);
    }

    private function clearFailedAttempts(string $email, string $ip): void
    {
        apcu_delete($this->throttleKey($email, $ip));
    }

    private function throttleKey(string $email, string $ip): string
    {
        return 'throttle:' . hash('sha256', $email . $ip);
    }

    private function auditLog(string $action, ?int $userId, array $context = []): void
    {
        $log = sprintf(
            "[%s] action=%s user_id=%s ip=%s %s\n",
            date('Y-m-d H:i:s'),
            $action,
            $userId ?? 'null',
            $_SERVER['REMOTE_ADDR'] ?? '',
            json_encode($context),
        );
        file_put_contents(BASE_PATH . '/storage/logs/auth.log', $log, FILE_APPEND | LOCK_EX);
    }
}
```

---

## CSRF Protection

```php
<?php
// src/Auth/Csrf.php
declare(strict_types=1);

namespace Auth;

use Helpers\Session;

final class Csrf
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY  = '_csrf_token';

    public static function generate(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(self::TOKEN_LENGTH)));
        }
        return Session::get(self::SESSION_KEY);
    }

    public static function token(): string
    {
        return self::generate();
    }

    public static function field(): string
    {
        $token = self::generate();
        return sprintf('<input type="hidden" name="_csrf_token" value="%s">', htmlspecialchars($token));
    }

    public static function verify(string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY, '');
        return hash_equals($stored, $token);
    }

    public static function validateRequest(): void
    {
        if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD', 'OPTIONS'], true)) return;

        $token = $_POST['_csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (!self::verify($token)) {
            http_response_code(419);
            die('CSRF token mismatch.');
        }
    }
}
```

---

## Password Reset Flow

```php
<?php
// src/Auth/PasswordReset.php
declare(strict_types=1);

namespace Auth;

use Repositories\UserRepository;

final class PasswordReset
{
    private const TOKEN_TTL_MINUTES = 60;

    public function __construct(private readonly UserRepository $users) {}

    public function sendResetLink(string $email): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) return true; // Don't reveal if email exists

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_TTL_MINUTES . ' minutes'));

        $this->users->savePasswordResetToken($user['id'], hash('sha256', $token), $expires);

        // Send email via skill-php-email
        $resetUrl = ($_ENV['APP_URL'] ?? '') . "/reset-password?token={$token}&email=" . urlencode($email);

        // Mail sending delegated to EmailService
        return true;
    }

    public function reset(string $email, string $token, string $newPassword): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) return false;

        $record = $this->users->findPasswordResetToken($user['id'], hash('sha256', $token));
        if ($record === null || strtotime($record['expires_at']) < time()) {
            return false;
        }

        $this->users->update($user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID),
        ]);

        $this->users->deletePasswordResetToken($user['id']);
        return true;
    }
}
```

---

## Security Headers Middleware

```php
<?php
// src/Middleware/SecurityHeaders.php
declare(strict_types=1);

namespace Middleware;

final class SecurityHeaders
{
    public function handle(array $request, callable $next): mixed
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // XSS filter (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

        // HSTS (only if HTTPS)
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Remove PHP version exposure
        header_remove('X-Powered-By');

        return $next($request);
    }
}
```

---

## Input Sanitization Helpers

```php
<?php
// src/Helpers/Sanitize.php
declare(strict_types=1);

namespace Helpers;

final class Sanitize
{
    /** Safe string — strips tags, trims */
    public static function string(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    /** Integer */
    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /** Email */
    public static function email(mixed $value): string
    {
        return (string) filter_var(trim((string) $value), FILTER_SANITIZE_EMAIL);
    }

    /** URL */
    public static function url(mixed $value): string
    {
        return (string) filter_var(trim((string) $value), FILTER_SANITIZE_URL);
    }

    /** HTML output — escape for display */
    public static function html(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Slug — lowercase alphanumeric + dash */
    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[áàäâ]/u', 'a', $value);
        $value = preg_replace('/[éèëê]/u', 'e', $value);
        $value = preg_replace('/[íìïî]/u', 'i', $value);
        $value = preg_replace('/[óòöô]/u', 'o', $value);
        $value = preg_replace('/[úùüû]/u', 'u', $value);
        $value = preg_replace('/[ñ]/u', 'n', $value);
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        return preg_replace('/[\s-]+/', '-', $value);
    }

    /** ISBN — strip spaces and dashes */
    public static function isbn(string $value): string
    {
        return preg_replace('/[\s\-]/', '', trim($value));
    }
}
```

---

## Auth Middleware (route protection)

```php
<?php
// src/Middleware/AuthMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Auth\AuthService;
use Auth\Permission;

final class AuthMiddleware
{
    public function __construct(private readonly AuthService $auth) {}

    public function handle(array $request, callable $next): mixed
    {
        if (!$this->auth->check()) {
            \Helpers\Session::flash('error', 'Debe iniciar sesión para continuar.');
            \Core\Response::redirect('/login');
        }
        return $next($request);
    }
}

final class GuestMiddleware
{
    public function __construct(private readonly AuthService $auth) {}

    public function handle(array $request, callable $next): mixed
    {
        if ($this->auth->check()) {
            \Core\Response::redirect('/dashboard');
        }
        return $next($request);
    }
}
```

---

## DB Schema for Auth

```sql
-- Users table with auth fields
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_hash   VARCHAR(255)  NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS role            ENUM('admin','librarian','member','guest') NOT NULL DEFAULT 'member';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active       TINYINT(1)    NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at   DATETIME      NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_ip   VARCHAR(45)   NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token  VARCHAR(100)  NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_expires DATETIME     NULL;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_password_resets_user (user_id),
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Workflow

1. **Nunca almacenar contraseña plana** — Siempre `password_hash($pass, PASSWORD_ARGON2ID)`.
2. **Regenerar sesión en login/logout** — `session_regenerate_id(true)` previene session fixation.
3. **CSRF en todos los formularios POST** — `Csrf::field()` en el form; `Csrf::validateRequest()` en el middleware.
4. **Verificar permiso, no solo rol** — `$auth->requirePermission(Permission::BooksCreate)` es más granular que `$auth->role()`.
5. **Escapar toda salida** — `Sanitize::html()` antes de imprimir datos de usuario.
6. **Auditar accesos sensibles** — Login, logout, cambios de contraseña, denegaciones de permiso.
7. **Fail closed** — Si no hay sesión activa, denegar; no asumir Guest implícito.
8. **Tokens de un solo uso** — Reset de contraseña: invalidar el token tras usarlo.
