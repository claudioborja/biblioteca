<?php
// app/Core/Request.php — Abstracción de la petición HTTP
declare(strict_types=1);

namespace Core;

final class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $cookies;
    private array $files;

    private function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri     = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path    = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->query   = $_GET;
        $this->post    = $_POST;
        $this->server  = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->files   = $_FILES;

        // Strip base path so routes match regardless of install directory
        // Use SCRIPT_NAME's directory, but go up one level if inside public/
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if (str_ends_with($basePath, '/public')) {
            $basePath = substr($basePath, 0, -7);
        }
        if ($basePath !== '' && str_starts_with($this->path, $basePath)) {
            $this->path = substr($this->path, strlen($basePath)) ?: '/';
        }

        // Support PUT/DELETE via _method field
        if ($this->method === 'POST' && isset($this->post['_method'])) {
            $override = strtoupper($this->post['_method']);
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                $this->method = $override;
            }
        }
    }

    public static function capture(): self
    {
        return new self();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function expectsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
