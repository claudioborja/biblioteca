<?php
// app/Middleware/SecurityHeadersMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Request;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        return $next($request);
    }
}
