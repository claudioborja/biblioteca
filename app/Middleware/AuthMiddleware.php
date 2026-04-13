<?php
// app/Middleware/AuthMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!Session::has('auth.user_id')) {
            return Response::redirect(BASE_URL . '/login?redirect=' . urlencode($request->path()));
        }

        return $next($request);
    }
}
