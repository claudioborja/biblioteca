---
name: skill-php-puro
description: "**WORKFLOW SKILL** — Pure PHP architecture without frameworks at expert level. USE FOR: MVC architecture from scratch; lightweight router (static, dynamic, regex routes); PSR-4 autoloading without Composer when needed; dependency injection container (manual and auto-wiring); native PHP template engine with layouts, partials, escaping; middleware pipeline; request/response abstraction; service layer; repository pattern with PDO; configuration management; error and exception handling; input validation and sanitization; pagination; flash messages; CSRF protection integration; PHP 8.x features (enums, readonly, match, named args, fibers); zero-framework ultra-lightweight code compatible with any shared hosting, VPS, cPanel, Plesk, DirectAdmin. DO NOT USE FOR: framework-specific patterns; frontend/JS; infrastructure configuration."
---

# Pure PHP — Professional Architecture Without Frameworks

## Core Philosophy

- **Zero mandatory dependencies**: The system must run with `php -S` or any shared hosting with PHP 7.4+.
- **PSR compliance without Composer**: Follow PSR-1, PSR-2, PSR-4 naming — even if autoloading is manual.
- **Explicit over magic**: No annotations, no reflection-heavy containers. Dependencies are declared, not discovered.
- **Single entry point**: All requests go through `public/index.php`.
- **Separation of concerns**: Router → Middleware → Controller → Service → Repository → Database.

---

## Project Structure

```
biblioteca/
├── public/
│   ├── index.php          ← single entry point
│   ├── .htaccess          ← Apache rewrite rules
│   └── assets/            ← css, js, images (public)
├── src/
│   ├── Core/
│   │   ├── App.php        ← bootstrap and container
│   │   ├── Router.php     ← request routing
│   │   ├── Request.php    ← request abstraction
│   │   ├── Response.php   ← response abstraction
│   │   ├── Container.php  ← DI container
│   │   ├── Middleware.php ← middleware pipeline
│   │   └── View.php       ← template engine
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── Middleware/
│   └── Helpers/
├── views/
│   ├── layouts/
│   │   └── main.php
│   └── partials/
├── config/
│   ├── app.php
│   ├── database.php
│   └── routes.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
└── bootstrap.php
```

---

## Entry Point & Bootstrap

```php
<?php
// public/index.php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('START_TIME', microtime(true));

require BASE_PATH . '/bootstrap.php';

$app = new \Core\App();
$app->run();
```

```php
<?php
// bootstrap.php
declare(strict_types=1);

// PSR-4 autoloader (no Composer required)
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core\\'        => BASE_PATH . '/src/Core/',
        'Controllers\\' => BASE_PATH . '/src/Controllers/',
        'Services\\'    => BASE_PATH . '/src/Services/',
        'Repositories\\'=> BASE_PATH . '/src/Repositories/',
        'Models\\'      => BASE_PATH . '/src/Models/',
        'Middleware\\'  => BASE_PATH . '/src/Middleware/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// Environment
$env = BASE_PATH . '/.env';
if (file_exists($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php.log');

set_exception_handler(function (\Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
    } else {
        require BASE_PATH . '/views/errors/500.php';
    }
    exit;
});
```

---

## Router

```php
<?php
// src/Core/Router.php
declare(strict_types=1);

namespace Core;

final class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];

    public function get(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    public function delete(string $path, array|callable $handler, string $name = ''): self
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array|callable $handler, string $name): self
    {
        $prefix = implode('', array_column($this->groupStack, 'prefix'));
        $middleware = array_merge(...array_column($this->groupStack, 'middleware') ?: [[]]);

        $fullPath = $prefix . $path;
        $pattern  = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern  = '#^' . $pattern . '$#';

        $route = compact('method', 'pattern', 'handler', 'middleware');
        $this->routes[] = $route;

        if ($name !== '') {
            $this->namedRoutes[$name] = $fullPath;
        }

        return $this;
    }

    public function dispatch(Request $request): array
    {
        $method = $request->method();
        $uri    = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
            }
        }

        return [];
    }

    public function route(string $name, array $params = []): string
    {
        $path = $this->namedRoutes[$name]
            ?? throw new \RuntimeException("Route [{$name}] not defined.");

        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", (string) $value, $path);
        }

        return $path;
    }
}
```

---

## Request & Response

```php
<?php
// src/Core/Request.php
declare(strict_types=1);

namespace Core;

final class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;

    public function __construct()
    {
        $this->get     = $_GET;
        $this->post    = $_POST;
        $this->server  = $_SERVER;
        $this->files   = $_FILES;
        $this->cookies = $_COOKIE;
    }

    public function method(): string
    {
        $override = $this->post['_method'] ?? $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '';
        return strtoupper($override ?: $this->server['REQUEST_METHOD']);
    }

    public function path(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim((string) $uri, '/');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        return str_contains($this->server['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function json(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body ?: '{}', true) ?? [];
    }

    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR']
            ?? $this->server['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    /** Sanitize a string input */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return htmlspecialchars(trim((string) $value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Get raw integer input */
    public function int(string $key, int $default = 0): int
    {
        return (int) filter_var($this->input($key, $default), FILTER_SANITIZE_NUMBER_INT);
    }
}
```

```php
<?php
// src/Core/Response.php
declare(strict_types=1);

namespace Core;

final class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        header("Location: {$url}", true, $status);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $view = BASE_PATH . "/views/errors/{$status}.php";
        if (file_exists($view)) require $view;
        else echo $message ?: "HTTP {$status}";
        exit;
    }

    public static function download(string $filePath, string $fileName): never
    {
        if (!file_exists($filePath)) self::abort(404);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
```

---

## Dependency Injection Container

```php
<?php
// src/Core/Container.php
declare(strict_types=1);

namespace Core;

final class Container
{
    private array $bindings  = [];
    private array $singletons = [];
    private array $instances  = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = ($this->singletons[$abstract])($this);
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // Auto-wire if class exists
        if (class_exists($abstract)) {
            return $this->build($abstract);
        }

        throw new \RuntimeException("Cannot resolve [{$abstract}] from container.");
    }

    private function build(string $class): object
    {
        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new \RuntimeException("[{$class}] is not instantiable.");
        }

        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    "Cannot resolve parameter [{$param->getName()}] in [{$class}]."
                );
            }
        }

        return $ref->newInstanceArgs($args);
    }
}
```

---

## View / Template Engine

```php
<?php
// src/Core/View.php
declare(strict_types=1);

namespace Core;

final class View
{
    private string $layout = 'main';
    private array  $sections = [];
    private string $currentSection = '';

    public function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require BASE_PATH . "/views/{$template}.php";
        $content = ob_get_clean();

        if ($this->layout === '') return $content;

        ob_start();
        require BASE_PATH . "/views/layouts/{$this->layout}.php";
        return ob_get_clean();
    }

    public function layout(string $name): void
    {
        $this->layout = $name;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = '';
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function partial(string $name, array $data = []): string
    {
        return $this->render("partials/{$name}", $data);
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
```

```php
<!-- views/layouts/main.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->e($view->yield('title', 'Biblioteca')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <?= $view->yield('head') ?>
</head>
<body>
    <?= $view->partial('navbar') ?>
    <main>
        <?= $content ?>
    </main>
    <?= $view->partial('footer') ?>
    <script src="/assets/js/app.js"></script>
    <?= $view->yield('scripts') ?>
</body>
</html>
```

---

## Input Validation

```php
<?php
// src/Core/Validator.php
declare(strict_types=1);

namespace Core;

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $ruleName, $param);
            }
        }

        return $this->errors === [];
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        match ($rule) {
            'required' => ($value === null || trim((string)$value) === '')
                && $this->addError($field, "El campo {$field} es obligatorio."),
            'email'    => (!filter_var($value, FILTER_VALIDATE_EMAIL))
                && $this->addError($field, "El campo {$field} debe ser un email válido."),
            'min'      => (strlen((string)$value) < (int)$param)
                && $this->addError($field, "El campo {$field} debe tener al menos {$param} caracteres."),
            'max'      => (strlen((string)$value) > (int)$param)
                && $this->addError($field, "El campo {$field} no puede superar {$param} caracteres."),
            'numeric'  => (!is_numeric($value))
                && $this->addError($field, "El campo {$field} debe ser numérico."),
            'integer'  => (filter_var($value, FILTER_VALIDATE_INT) === false)
                && $this->addError($field, "El campo {$field} debe ser un entero."),
            'url'      => (!filter_var($value, FILTER_VALIDATE_URL))
                && $this->addError($field, "El campo {$field} debe ser una URL válida."),
            'in'       => (!in_array($value, explode(',', $param ?? ''), true))
                && $this->addError($field, "El valor de {$field} no es válido."),
            'date'     => (!strtotime((string)$value))
                && $this->addError($field, "El campo {$field} debe ser una fecha válida."),
            'confirmed'=> ($value !== ($GLOBALS['_POST']["{$field}_confirmation"] ?? null))
                && $this->addError($field, "La confirmación de {$field} no coincide."),
            default    => null,
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array   { return $this->errors; }
    public function passes(): bool    { return $this->errors === []; }
    public function fails(): bool     { return $this->errors !== []; }

    public function first(string $field): string
    {
        return $this->errors[$field][0] ?? '';
    }
}
```

---

## PDO Database Connection

```php
<?php
// src/Core/Database.php
declare(strict_types=1);

namespace Core;

final class Database
{
    private static ?\PDO $instance = null;

    public static function connect(): \PDO
    {
        if (self::$instance !== null) return self::$instance;

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST']     ?? 'localhost',
            $_ENV['DB_PORT']     ?? '3306',
            $_ENV['DB_DATABASE'] ?? '',
        );

        self::$instance = new \PDO($dsn, $_ENV['DB_USERNAME'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_PERSISTENT         => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);

        return self::$instance;
    }
}
```

---

## Base Repository

```php
<?php
// src/Repositories/BaseRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

abstract class BaseRepository
{
    protected \PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE deleted_at IS NULL
             ORDER BY {$this->primaryKey} DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL";
        if ($where !== '') $sql .= " AND {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET deleted_at = NOW() WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$id]);
    }

    protected function paginate(string $sql, array $params, int $perPage, int $page): array
    {
        $total = (int) $this->db->query("SELECT COUNT(*) FROM ({$sql}) t")->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("{$sql} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }
}
```

---

## Flash Messages & Session

```php
<?php
// src/Helpers/Session.php
declare(strict_types=1);

namespace Helpers;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('biblioteca_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }
}
```

---

## .htaccess (Apache)

```apache
# public/.htaccess
Options -Indexes
RewriteEngine On

# Deny access to hidden files
RewriteRule ^\.         - [F,L]

# Skip real files and directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route everything through index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

## Nginx Config

```nginx
server {
    listen 80;
    server_name biblioteca.local;
    root /var/www/biblioteca/public;
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~* \.(env|log|sql|bak)$ {
        deny all;
    }
}
```

---

## Workflow

1. **Entry point único** — Todo entra por `public/index.php`; nada fuera de `public/` es accesible por HTTP.
2. **Autoloader primero** — Registrar `spl_autoload_register` antes de cualquier `require`.
3. **Container como fuente de verdad** — Todos los objetos se resuelven desde el container.
4. **Validar en el controller** — Nunca confiar en datos crudos de `$_POST`/`$_GET`.
5. **Repositorio para todo acceso a BD** — Nunca PDO directo en controllers o views.
6. **Views solo presentan** — Cero lógica de negocio en templates; solo `echo`, `foreach`, `if`.
7. **Escapar siempre la salida** — `View::e()` en cada variable de usuario impresa en HTML.
8. **Flash antes de redirect** — Guardar mensajes en sesión antes de cualquier `Response::redirect()`.
