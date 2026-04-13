<?php
// app/Middleware/CsrfMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Core\Session;

final class CsrfMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
            $token = $request->post('_csrf_token', '');
            $sessionToken = Session::get('_csrf_token', '');

            if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
                http_response_code(419);
                echo 'Token CSRF inválido.';
                exit;
            }
        }

        // Generate token for next request
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return $next($request);
    }

    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return Session::get('_csrf_token');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::token() . '">';
    }
}
