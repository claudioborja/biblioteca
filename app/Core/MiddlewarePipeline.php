<?php
// app/Core/MiddlewarePipeline.php — Pipeline de middleware
declare(strict_types=1);

namespace Core;

final class MiddlewarePipeline
{
    private Container $container;
    private array $middleware = [];

    private const MIDDLEWARE_MAP = [
        'auth'  => \Middleware\AuthMiddleware::class,
        'guest' => \Middleware\GuestMiddleware::class,
        'csrf'  => \Middleware\CsrfMiddleware::class,
        'role'  => \Middleware\RoleMiddleware::class,
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function through(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function run(Request $request, callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function (callable $next, string $middlewareKey) {
                return function (Request $request) use ($middlewareKey, $next) {
                    $middleware = $this->resolveMiddleware($middlewareKey);
                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    private function resolveMiddleware(string $key): object
    {
        // Handle role:admin,librarian syntax
        $params = [];
        if (str_contains($key, ':')) {
            [$key, $paramStr] = explode(':', $key, 2);
            $params = explode(',', $paramStr);
        }

        $class = self::MIDDLEWARE_MAP[$key] ?? $key;

        if (!class_exists($class)) {
            throw new \RuntimeException("Middleware no encontrado: {$key}");
        }

        $instance = new $class();

        if ($params !== [] && method_exists($instance, 'setParams')) {
            $instance->setParams($params);
        }

        return $instance;
    }
}
