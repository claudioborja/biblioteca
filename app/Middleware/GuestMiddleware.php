<?php
// app/Middleware/GuestMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Core\Session;

final class GuestMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (Session::has('auth.user_id')) {
            return Response::redirect(BASE_URL . '/account');
        }

        return $next($request);
    }
}
