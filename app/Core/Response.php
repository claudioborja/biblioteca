<?php
// app/Core/Response.php — Abstracción de respuesta HTTP
declare(strict_types=1);

namespace Core;

final class Response
{
    private int $statusCode;
    private string $body;
    private array $headers = [];

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new self($body, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->headers['Location'] = $url;
        return $response;
    }

    public static function download(string $filePath, string $filename, string $mime = 'application/octet-stream'): void
    {
        if (!file_exists($filePath)) {
            self::abort(404);
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public static function abort(int $code): never
    {
        http_response_code($code);
        $errorFile = BASE_PATH . "/views/errors/{$code}.php";
        if (file_exists($errorFile)) {
            require $errorFile;
        }
        exit;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
