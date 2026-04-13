<?php
// app/Core/View.php — Motor de plantillas nativo
declare(strict_types=1);

namespace Core;

final class View
{
    private string $basePath;
    private array $shared = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = [], string $layout = 'layouts/app'): string
    {
        $data = array_merge($this->shared, $data);

        // Render content
        $content = $this->renderTemplate($template, $data);

        // Wrap in layout if specified
        if ($layout !== '') {
            $data['content'] = $content;
            return $this->renderTemplate($layout, $data);
        }

        return $content;
    }

    private function renderTemplate(string $template, array $data): string
    {
        $file = $this->basePath . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Vista no encontrada: {$template}");
        }

        extract($data, EXTR_SKIP);

        // Ensure template changes are reflected even when opcache timestamp checks are disabled.
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }

        ob_start();
        require $file;
        return ob_get_clean();
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
