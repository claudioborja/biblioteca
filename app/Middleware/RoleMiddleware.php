<?php
// app/Middleware/RoleMiddleware.php
declare(strict_types=1);

namespace Middleware;

use Core\Request;
use Core\Response;
use Core\Session;

final class RoleMiddleware
{
    private array $allowedRoles = [];

    public function setParams(array $params): void
    {
        $this->allowedRoles = $params;
    }

    public function handle(Request $request, callable $next): mixed
    {
        $role = Session::get('auth.role', 'guest');

        if (!in_array($role, $this->allowedRoles, true)) {
            Response::abort(403);
        }

        return $next($request);
    }
}
