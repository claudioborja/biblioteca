---
name: skill-security
description: "**WORKFLOW SKILL** — Comprehensive application security for pure PHP systems at expert level. USE FOR: OWASP Top 10 prevention in PHP (SQL injection, XSS, CSRF, IDOR, command injection, open redirect, mass assignment, SSRF, path traversal, security misconfiguration); Content Security Policy (CSP) advanced configuration; Subresource Integrity (SRI); PHP security hardening (php.ini, disable_functions); secure random generation; timing attack prevention with hash_equals; IDOR prevention (ownership checks, indirect references); command injection prevention; open redirect prevention; mass assignment protection; SSRF prevention; dependency audit (composer audit); security code review checklist; security headers complete suite; input validation vs output encoding distinction; file inclusion prevention; HTTP parameter pollution; clickjacking prevention; security testing approach; incident response checklist. USE THIS SKILL alongside skill-auth-php (handles authentication flows) and skill-php-files (handles upload validation). DO NOT USE FOR: penetration testing methodology; network security; OAuth2 provider configuration."
---

# Security — Comprehensive PHP Application Security

## Core Philosophy

- **Defense in depth**: Never rely on a single control. Validate input + parameterize queries + escape output + enforce permissions — all together.
- **Deny by default**: Reject everything not explicitly allowed. Never blacklist; always whitelist.
- **Least privilege everywhere**: DB user, file permissions, PHP functions, user roles — minimum necessary, nothing more.
- **Security is not a feature**: It is a cross-cutting constraint applied at every layer, every time.
- **Assume breach**: Log, monitor, and design so that when (not if) something fails, damage is contained.

---

## OWASP Top 10 — PHP Prevention Reference

### A01 — Broken Access Control (IDOR)

The most critical vulnerability: user A accesses user B's data by changing an ID in the URL.

```php
<?php
// BAD — no ownership check
public function showLoan(int $loanId): void
{
    $loan = $this->loans->findById($loanId); // any user can see any loan
    echo $this->view->render('loans/show', compact('loan'));
}

// GOOD — always verify ownership
public function showLoan(int $loanId): void
{
    $loan = $this->loans->findById($loanId);

    if ($loan === null) {
        \Core\Response::abort(404);
    }

    // Ownership check: the loan must belong to the authenticated user
    // unless the user is admin/librarian
    if ($loan['user_id'] !== $this->auth->id()
        && $this->auth->cannot(\Auth\Permission::LoansManage)) {
        \Core\Logger::warning('IDOR attempt on loan', [
            'loan_id'       => $loanId,
            'loan_owner'    => $loan['user_id'],
            'requester'     => $this->auth->id(),
        ]);
        \Core\Response::abort(403);
    }

    echo $this->view->render('loans/show', compact('loan'));
}
```

```php
// GOOD — use indirect references (map real IDs in session)
// Instead of exposing /downloads/42, use /downloads/a3f8c1
// and resolve the real ID server-side from the user's session context

final class IndirectReference
{
    /** Generate a session-scoped token for a resource */
    public static function generate(int $realId, string $scope): string
    {
        $token = bin2hex(random_bytes(8));
        $_SESSION['indirect'][$scope][$token] = $realId;
        return $token;
    }

    /** Resolve token back to real ID — returns null if not owned by this session */
    public static function resolve(string $token, string $scope): ?int
    {
        return $_SESSION['indirect'][$scope][$token] ?? null;
    }
}
```

**Checklist for every controller method:**
- [ ] Is the user authenticated?
- [ ] Does the user have the required permission?
- [ ] Does the resource belong to this user (or is the user an admin)?
- [ ] Is the resource soft-deleted or inactive?

---

### A02 — Cryptographic Failures

```php
<?php
// Passwords — ARGON2ID is the gold standard; BCRYPT as fallback
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,   // 64 MB
    'time_cost'   => 4,
    'threads'     => 2,
]);
$valid = password_verify($password, $hash);

// Secure random — always use random_bytes()
$token = bin2hex(random_bytes(32));         // 64-char hex string
$key   = base64_encode(random_bytes(32));   // 256-bit key

// Token comparison — always hash_equals() to prevent timing attacks
$storedHash  = hash('sha256', $storedToken);
$incomingHash = hash('sha256', $incomingToken);
if (!hash_equals($storedHash, $incomingHash)) {
    // Reject
}

// Sensitive data in DB — AES encryption for PII
$key       = hex2bin($_ENV['ENCRYPTION_KEY']); // 32 bytes from env
$iv        = random_bytes(16);
$encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, 0, $iv);
$stored    = base64_encode($iv . $encrypted); // store IV + ciphertext together

// Decrypt
$decoded   = base64_decode($stored);
$iv        = substr($decoded, 0, 16);
$encrypted = substr($decoded, 16);
$plaintext = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);

// NEVER use MD5 or SHA1 for passwords or tokens
// NEVER store tokens in plain text — always hash('sha256', $token) before storing
// NEVER use rand() or mt_rand() for security tokens — use random_bytes()
```

---

### A03 — Injection

#### SQL Injection

```php
<?php
// BAD — string concatenation
$users = $db->query("SELECT * FROM users WHERE email = '{$_POST['email']}'")->fetchAll();

// GOOD — prepared statements always
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
$stmt->execute([$_POST['email']]);
$user = $stmt->fetch();

// GOOD — named parameters for complex queries
$stmt = $db->prepare("
    SELECT * FROM books
    WHERE category_id = :category
      AND year BETWEEN :from AND :to
      AND deleted_at IS NULL
");
$stmt->execute([
    'category' => $filters['category_id'],
    'from'     => $filters['year_from'],
    'to'       => $filters['year_to'],
]);

// Dynamic ORDER BY — whitelist allowed columns (cannot parameterize column names)
$allowedSort = ['title', 'author', 'year', 'created_at'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort, true)
    ? $_GET['sort']
    : 'title';
$stmt = $db->query("SELECT * FROM books ORDER BY {$sort} ASC");

// Dynamic IN clause — build safely
$ids = array_map('intval', $_POST['ids'] ?? []);
if ($ids !== []) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM books WHERE id IN ({$placeholders})");
    $stmt->execute($ids);
}
```

#### Command Injection

```php
<?php
// BAD — user input in shell command
exec("convert " . $_POST['filename'] . " output.jpg");

// BAD — even with escapeshellarg, avoid if possible
exec("convert " . escapeshellarg($_POST['filename']) . " output.jpg");

// GOOD — use PHP native functions instead of shell commands
// Image conversion: use GD or Imagick (PHP extensions)
// File operations: use PHP file functions
// NEVER pass user input to exec(), shell_exec(), system(), passthru(), popen()

// If shell execution is absolutely required:
final class SafeShell
{
    private const ALLOWED_COMMANDS = ['convert', 'ffprobe'];

    public static function run(string $command, array $args): string
    {
        if (!in_array($command, self::ALLOWED_COMMANDS, true)) {
            throw new \InvalidArgumentException("Command not allowed: {$command}");
        }
        // Escape every argument individually
        $escaped = array_map('escapeshellarg', $args);
        $cmd     = escapeshellcmd($command) . ' ' . implode(' ', $escaped);
        return (string) shell_exec($cmd);
    }
}
```

#### Path Traversal

```php
<?php
// BAD — user controls the path
$file = BASE_PATH . '/storage/' . $_GET['file'];
readfile($file);

// GOOD — sanitize and validate path stays within allowed directory
final class SafePath
{
    public static function resolve(string $userInput, string $allowedBase): ?string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $userInput);

        // Normalize and resolve
        $fullPath = realpath($allowedBase . '/' . $input);

        // Verify the resolved path is still inside the allowed base
        if ($fullPath === false || !str_starts_with($fullPath, realpath($allowedBase) . DIRECTORY_SEPARATOR)) {
            return null; // Path traversal attempt
        }

        return $fullPath;
    }
}

// Usage
$safe = SafePath::resolve($_GET['file'], BASE_PATH . '/storage/uploads/covers');
if ($safe === null) \Core\Response::abort(400, 'Invalid file path');
readfile($safe);
```

#### File Inclusion

```php
<?php
// BAD — user controls which file to include
include $_GET['page'] . '.php';

// GOOD — whitelist allowed views
$allowedPages = ['home', 'catalog', 'about', 'contact'];
$page = in_array($_GET['page'] ?? '', $allowedPages, true)
    ? $_GET['page']
    : 'home';
include BASE_PATH . "/views/{$page}.php";
```

---

### A04 — Insecure Design

```php
<?php
// Mass Assignment — never pass raw $_POST to insert/update
// BAD
$this->users->update($id, $_POST);  // user could add role=admin

// GOOD — whitelist allowed fields explicitly
$allowed = ['name', 'email', 'phone', 'address'];
$data = array_intersect_key($_POST, array_flip($allowed));
$this->users->update($id, $data);

// GOOD — dedicated DTO/value object
final class UpdateProfileData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}

    public static function fromRequest(array $post): self
    {
        return new self(
            name:  trim((string) ($post['name']  ?? '')),
            email: trim((string) ($post['email'] ?? '')),
            phone: isset($post['phone']) ? trim((string) $post['phone']) : null,
        );
    }
}
```

---

### A05 — Security Misconfiguration

```ini
; php.ini — production hardening
expose_php              = Off       ; hide PHP version from headers
display_errors          = Off       ; never show errors to users
display_startup_errors  = Off
log_errors              = On
error_log               = /var/log/php/app.log

; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,\
                    curl_exec,curl_multi_exec,parse_ini_file,show_source,\
                    phpinfo,pcntl_exec

; File uploads
file_uploads            = On
upload_max_filesize     = 20M
upload_tmp_dir          = /tmp

; Sessions
session.use_strict_mode     = 1
session.cookie_httponly     = 1
session.cookie_secure       = 1   ; HTTPS only
session.cookie_samesite     = Lax
session.use_only_cookies    = 1
session.gc_maxlifetime      = 1800
session.entropy_length      = 32

; Open basedir — restrict PHP file access
open_basedir = /var/www/biblioteca/:/tmp/

; Disable URL include
allow_url_fopen  = Off
allow_url_include = Off
```

---

### A06 — Vulnerable and Outdated Components

```bash
# Audit Composer dependencies for known vulnerabilities
composer audit

# Update all dependencies
composer update

# Check specific package
composer audit --format=json | jq '.advisories'

# List outdated packages
composer outdated

# Check PHP version vulnerabilities
php -v   # ensure not EOL (End of Life)
# PHP EOL dates: https://www.php.net/supported-versions.php

# Automated audit in deploy script
if ! composer audit --no-dev --quiet; then
    echo "Security vulnerabilities found in dependencies. Aborting deploy."
    exit 1
fi
```

---

### A07 — Identification and Authentication Failures

```php
<?php
// Covered in skill-auth-php — key points summary:

// 1. password_hash(PASSWORD_ARGON2ID) — never MD5/SHA1
// 2. session_regenerate_id(true) on login AND privilege escalation
// 3. Brute force: max 5 attempts per 15 min per IP+email
// 4. Remember-me: store hash('sha256', $token) — never plain token
// 5. Password reset tokens: single-use, expire in 60 min, hash before storing
// 6. Account lockout on repeated failures
// 7. Secure session cookie flags: httponly, secure, samesite=Lax

// Additional: Enumerate-safe responses
// BAD — reveals whether email exists
if (!$user) echo "Email not found";
if ($user && !password_verify($pass, $user['hash'])) echo "Wrong password";

// GOOD — same message for both cases
if (!$user || !password_verify($pass, $user['hash'] ?? '')) {
    echo "Credenciales incorrectas"; // same message always
}
```

---

### A08 — Software and Data Integrity Failures

```php
<?php
// Subresource Integrity (SRI) for CDN assets
// If loading CSS/JS from a CDN, verify integrity

// Generate SRI hash
// cat file.js | openssl dgst -sha384 -binary | openssl base64 -A

// In views:
?>
<link rel="stylesheet"
      href="https://cdn.example.com/tailwind.min.css"
      integrity="sha384-XXXXXX"
      crossorigin="anonymous">

<script src="https://cdn.example.com/alpinejs.min.js"
        integrity="sha384-XXXXXX"
        crossorigin="anonymous"></script>

<?php
// Prefer self-hosting assets over CDN for sensitive applications
// This eliminates the need for SRI entirely

// File integrity verification on deploy
final class IntegrityCheck
{
    public static function verifyFile(string $path, string $expectedSha256): bool
    {
        if (!file_exists($path)) return false;
        return hash_file('sha256', $path) === $expectedSha256;
    }
}
```

---

### A09 — Security Logging and Monitoring Failures

```php
<?php
// Covered in skill-php-logging — key security events to log:

final class SecurityLog
{
    public static function loginFailed(string $email, string $ip): void
    {
        \Core\Logger::warning('Login failed', compact('email', 'ip'));
    }

    public static function loginSuccess(int $userId, string $ip): void
    {
        \Core\Logger::info('Login success', compact('userId', 'ip'));
    }

    public static function accessDenied(string $resource, ?int $userId, string $ip): void
    {
        \Core\Logger::warning('Access denied', compact('resource', 'userId', 'ip'));
    }

    public static function idorAttempt(string $resource, int $resourceId, ?int $userId): void
    {
        \Core\Logger::error('Possible IDOR attempt', compact('resource', 'resourceId', 'userId'));
    }

    public static function suspiciousInput(string $field, string $value, string $ip): void
    {
        \Core\Logger::warning('Suspicious input detected', [
            'field' => $field,
            'value' => mb_substr($value, 0, 100),
            'ip'    => $ip,
        ]);
    }

    public static function csrfFailure(string $uri, string $ip): void
    {
        \Core\Logger::error('CSRF token mismatch', compact('uri', 'ip'));
    }
}
```

---

### A10 — Server-Side Request Forgery (SSRF)

```php
<?php
// SSRF: attacker makes server fetch a URL they control
// Risk in library: ISBN lookup, cover image download from URL

final class SafeHttpClient
{
    // Allowed URL schemes
    private const ALLOWED_SCHEMES = ['https', 'http'];

    // Blocked IP ranges (RFC 1918 private + loopback + link-local)
    private const BLOCKED_CIDRS = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '::1/128',
        'fc00::/7',
    ];

    public static function fetch(string $url, int $timeoutSeconds = 5): ?string
    {
        $parsed = parse_url($url);

        // Validate scheme
        if (!in_array($parsed['scheme'] ?? '', self::ALLOWED_SCHEMES, true)) {
            throw new \InvalidArgumentException("URL scheme not allowed.");
        }

        // Resolve hostname and validate IP
        $ip = gethostbyname($parsed['host'] ?? '');
        if (self::isBlockedIp($ip)) {
            throw new \InvalidArgumentException("URL resolves to a private/blocked IP.");
        }

        $ctx = stream_context_create(['http' => [
            'timeout'       => $timeoutSeconds,
            'max_redirects' => 3,
            'ignore_errors' => true,
            'header'        => "User-Agent: BibliotecaApp/1.0\r\n",
        ]]);

        return @file_get_contents($url, false, $ctx) ?: null;
    }

    private static function isBlockedIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;

        $long = ip2long($ip);
        if ($long === false) return false;

        // IPv4 CIDR check
        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (!str_contains($cidr, ':')) { // skip IPv6 for ip2long
                [$network, $bits] = explode('/', $cidr);
                $mask = ~((1 << (32 - (int)$bits)) - 1);
                if ((ip2long($network) & $mask) === ($long & $mask)) return true;
            }
        }

        return false;
    }
}
```

---

### Open Redirect

```php
<?php
// BAD — redirect to any URL from user input
header('Location: ' . $_GET['next']);

// GOOD — validate redirect is internal
final class SafeRedirect
{
    public static function to(string $url, string $fallback = '/'): never
    {
        $safe = self::isSafeUrl($url) ? $url : $fallback;
        header('Location: ' . $safe, true, 302);
        exit;
    }

    private static function isSafeUrl(string $url): bool
    {
        // Allow only relative paths or same host
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true; // relative path — safe
        }

        $parsed  = parse_url($url);
        $appHost = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_HOST);

        return isset($parsed['host']) && $parsed['host'] === $appHost;
    }
}

// Usage: after login, redirect to intended page
$next = $_GET['next'] ?? '/dashboard';
SafeRedirect::to($next, '/dashboard');
```

---

## Content Security Policy (CSP) — Complete

```php
<?php
// src/Middleware/ContentSecurityPolicy.php
declare(strict_types=1);

namespace Middleware;

final class ContentSecurityPolicy
{
    public static function header(bool $reportOnly = false): void
    {
        $nonce = base64_encode(random_bytes(16));
        $_REQUEST['_csp_nonce'] = $nonce; // pass to views

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",           // inline scripts need nonce
            "style-src 'self' 'unsafe-inline'",              // inline styles (needed for many CSS)
            "img-src 'self' data: blob:",                    // data URIs for barcodes
            "font-src 'self'",
            "connect-src 'self'",                            // AJAX/fetch to same origin only
            "form-action 'self'",                            // forms post to same origin only
            "frame-ancestors 'none'",                        // clickjacking prevention
            "base-uri 'self'",                               // prevent base tag injection
            "object-src 'none'",                             // no Flash/plugins
            "upgrade-insecure-requests",                     // force HTTPS for sub-resources
        ];

        $policy = implode('; ', $directives);
        $headerName = $reportOnly
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        header("{$headerName}: {$policy}");
    }
}
```

```php
<!-- In views — use nonce for inline scripts -->
<script nonce="<?= htmlspecialchars($_REQUEST['_csp_nonce'] ?? '', ENT_QUOTES) ?>">
    // Inline script with nonce — allowed by CSP
    document.getElementById('search-input').focus();
</script>
```

---

## Complete Security Headers

```php
<?php
// src/Middleware/SecurityHeaders.php — complete version
declare(strict_types=1);

namespace Middleware;

final class SecurityHeaders
{
    public function handle(array $request, callable $next): mixed
    {
        // Anti-clickjacking (also in CSP frame-ancestors)
        header('X-Frame-Options: DENY');

        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // Referrer — don't leak URL to third parties
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Remove PHP version fingerprint
        header_remove('X-Powered-By');

        // Permissions Policy — disable browser features not used
        header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

        // HSTS — HTTPS only, 1 year, include subdomains
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // CSP with nonce
        ContentSecurityPolicy::header();

        // Cross-Origin policies
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        return $next($request);
    }
}
```

---

## Input Validation vs Output Encoding

```php
<?php
// These are DIFFERENT concerns — both are required

// 1. INPUT VALIDATION — at entry point (controller/request)
//    Reject data that doesn't match expected format
//    Never sanitize and accept; validate and reject

$email = $_POST['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // REJECT — don't store, don't process
    throw new \InvalidArgumentException('Email inválido');
}

$age = (int) ($_POST['age'] ?? 0);
if ($age < 0 || $age > 150) {
    throw new \InvalidArgumentException('Edad inválida');
}

// 2. OUTPUT ENCODING — at output point (views)
//    Encode data for the context it's being placed in

// HTML context — encode for HTML
echo htmlspecialchars($userInput, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// HTML attribute context
echo htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8');

// JavaScript context — JSON encode
echo json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// URL context
echo urlencode($queryParam);
echo rawurlencode($pathSegment);

// CSS context — only allow known-safe values (whitelist)
$safeColor = in_array($color, ['red', 'blue', 'green'], true) ? $color : 'black';

// SQL context — ALWAYS prepared statements (never manual escaping)
```

---

## XSS Prevention Systematic Approach

```php
<?php
// Three types of XSS and how to prevent each:

// 1. REFLECTED XSS — user input echoed back in response
// Prevention: encode output with htmlspecialchars()
echo htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8');

// 2. STORED XSS — malicious data saved in DB and displayed later
// Prevention: encode on OUTPUT (not on input — don't store mangled data)
// Store raw data; encode when displaying
$bookTitle = $row['title'];                                        // raw from DB
echo htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8');           // encode on display

// 3. DOM-BASED XSS — JavaScript reads from URL/storage and writes to DOM
// Prevention: in JavaScript, use textContent not innerHTML for user data
// BAD:
// document.getElementById('msg').innerHTML = location.hash;
// GOOD:
// document.getElementById('msg').textContent = decodeURIComponent(location.hash.slice(1));

// Rich text (if needed) — use HTML purifier, not raw HTML
// For library: book descriptions may contain HTML
// Use a simple whitelist approach:
final class HtmlPurifier
{
    private const ALLOWED_TAGS = '<p><br><strong><em><ul><ol><li><h3><h4><blockquote>';

    public static function clean(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);
        // Remove event handlers from remaining tags
        $clean = preg_replace('/\s*on\w+="[^"]*"/i', '', $clean);
        $clean = preg_replace('/\s*on\w+=\'[^\']*\'/i', '', $clean);
        return $clean;
    }
}
```

---

## PHP Security Configuration Checklist

```bash
#!/usr/bin/env bash
# scripts/security-check.php — run on deploy to verify security settings

php -r "
\$checks = [
    'expose_php OFF'              => ini_get('expose_php') === '',
    'display_errors OFF'          => ini_get('display_errors') === '',
    'allow_url_include OFF'       => ini_get('allow_url_include') === '',
    'session.cookie_httponly ON'  => (bool) ini_get('session.cookie_httponly'),
    'session.use_strict_mode ON'  => (bool) ini_get('session.use_strict_mode'),
    'session.cookie_samesite set' => ini_get('session.cookie_samesite') !== '',
];
foreach (\$checks as \$check => \$pass) {
    echo (\$pass ? '✓' : '✗') . ' ' . \$check . PHP_EOL;
}
"
```

---

## Security Code Review Checklist

Apply to every pull request / code change:

```
Authentication & Session
  [ ] Password hashed with password_hash(ARGON2ID|BCRYPT)
  [ ] session_regenerate_id(true) on login and privilege change
  [ ] Session cookies: httponly=true, secure=true, samesite=Lax

Authorization
  [ ] Every controller method checks authentication
  [ ] Every resource access checks ownership (IDOR prevention)
  [ ] Permission checked before action, not just before rendering

Input Handling
  [ ] No raw $_GET/$_POST/$_COOKIE passed to DB queries
  [ ] All DB queries use prepared statements
  [ ] Dynamic column/table names use whitelists
  [ ] File paths validated with realpath() + prefix check
  [ ] No user input in exec/shell_exec/system/popen

Output Encoding
  [ ] All user-controlled output through htmlspecialchars()
  [ ] JSON output uses json_encode() not string concatenation
  [ ] URL parameters through urlencode()

Redirects
  [ ] All redirects validate the target URL (SafeRedirect::to())
  [ ] No open redirects from query parameters

Cryptography
  [ ] Sensitive tokens use random_bytes()
  [ ] Token comparison uses hash_equals()
  [ ] No MD5/SHA1 for security purposes
  [ ] Sensitive fields encrypted at rest if needed

File Operations
  [ ] Upload MIME detected via finfo (not $_FILES['type'])
  [ ] Uploaded files stored outside web root
  [ ] Generated filenames are random (not user-supplied)

HTTP Security
  [ ] CSRF token in all state-changing forms
  [ ] Security headers set in middleware
  [ ] CSP header includes nonce for inline scripts

Logging
  [ ] Auth failures logged
  [ ] Access denials logged
  [ ] No passwords/tokens/PII in log entries

Dependencies
  [ ] composer audit run (zero high/critical advisories)
  [ ] PHP version not EOL
```

---

## Dependency Audit

```bash
# Run on every deploy — fail if vulnerabilities found
composer audit

# Output format options
composer audit --format=table
composer audit --format=json
composer audit --format=plain

# Check only production dependencies
composer audit --no-dev

# Ignore a specific advisory (with documented reason)
# Add to composer.json:
# "config": {
#   "audit": {
#     "ignore": ["CVE-2023-XXXX"]
#   }
# }

# Automate in deploy script
if ! composer audit --no-dev --quiet 2>/dev/null; then
    echo "ERROR: Security vulnerabilities found. Review with: composer audit"
    exit 1
fi
```

---

## Timing Attack Prevention

```php
<?php
// Any comparison involving secrets must use hash_equals()
// hash_equals() takes the same time regardless of where strings differ

// BAD — early exit leaks timing information
if ($userToken !== $storedToken) { ... }
if ($userToken == $storedToken)  { ... }

// GOOD — constant-time comparison
if (!hash_equals($storedToken, $userToken)) { ... }

// For hashed tokens (always hash before comparing):
$storedHash = hash('sha256', $knownToken);
$inputHash  = hash('sha256', $userInput);
if (!hash_equals($storedHash, $inputHash)) { ... }

// Password verification already uses constant-time internally
password_verify($input, $hash); // safe — do not re-implement

// Prevent username enumeration via timing (auth flow)
$user = $this->users->findByEmail($email);
$hash = $user['password_hash'] ?? password_hash('dummy', PASSWORD_BCRYPT); // always hash
$valid = password_verify($password, $hash) && $user !== null;
```

---

## HTTP Parameter Pollution

```php
<?php
// PHP uses the last value when duplicate params exist
// ?page=1&page=99 → $_GET['page'] = '99'
// ?ids[]=1&ids[]=2 → $_GET['ids'] = [1, 2]

// Always cast and validate — never trust array structure
$page = max(1, (int) ($_GET['page'] ?? 1));  // safe int

// Protect against array injection
$sort = (string) ($_GET['sort'] ?? 'title');  // cast to string, not array

// Reject unexpected array parameters
if (is_array($_GET['q'] ?? '')) {
    \Core\Response::abort(400);
}
```

---

## Incident Response Checklist

```
When a security incident is suspected:

1. CONTAIN
   [ ] Take the affected system offline or restrict access
   [ ] Preserve logs (copy before rotation)
   [ ] Revoke compromised tokens/sessions

2. ASSESS
   [ ] Review auth.log for the attack timeline
   [ ] Check for unauthorized data access (IDOR patterns in logs)
   [ ] Verify DB integrity (unexpected records, modified data)

3. REMEDIATE
   [ ] Patch the vulnerability
   [ ] Force all users to re-authenticate (session invalidation)
   [ ] Rotate all secrets: APP_KEY, DB password, SMTP credentials
   [ ] Re-hash passwords if breach involved the users table

4. NOTIFY
   [ ] Inform affected users if PII was accessed
   [ ] Document the incident, root cause, and fix

5. PREVENT
   [ ] Add a test that would have caught this vulnerability
   [ ] Update the security checklist
   [ ] Review similar code paths in the application
```

---

## Workflow

1. **Aplicar el checklist en cada PR** — No es opcional; es parte de la definición de "terminado".
2. **IDOR en cada método de controller** — Antes de devolver cualquier recurso, verificar que pertenece al usuario autenticado.
3. **Prepared statements sin excepción** — Cero concatenación de strings en SQL. Si el ORM no soporta parametrización, no usar ese ORM.
4. **`htmlspecialchars()` en toda salida** — El helper `View::e()` de skill-php-puro debe usarse en cada variable impresa.
5. **`hash_equals()` para tokens** — Cualquier comparación de secretos, tokens o hashes usa `hash_equals()`.
6. **`SafeRedirect::to()` para redirects** — Ningún redirect acepta una URL directamente del request sin validación.
7. **`composer audit` en cada deploy** — Si falla, el deploy no continúa.
8. **Log de eventos de seguridad** — Login fallido, acceso denegado, CSRF mismatch, IDOR detectado — todo en `auth.log`.
