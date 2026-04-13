<?php
// app/Middleware/RequestLoggerMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Logger;
use Core\Request;

final class RequestLoggerMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000, 2);

        Logger::info('Request', [
            'method'   => $request->method(),
            'path'     => $request->path(),
            'duration' => "{$duration}ms",
            'ip'       => $request->ip(),
        ]);

        return $response;
    }
}
