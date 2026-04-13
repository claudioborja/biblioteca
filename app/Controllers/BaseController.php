<?php
// app/Controllers/BaseController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Session;
use Core\View;

abstract class BaseController
{
    protected \PDO $db;
    protected View $view;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->view = new View(BASE_PATH . '/views');
    }

    protected function resolveAuthUser(): ?array
    {
        $userId = (int) Session::get('auth.user_id');
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return [
            'id'                => $user['id'],
            'name'              => $user['name'],
            'email'             => $user['email'],
            'role'              => $user['role'],
            'user_number' => $user['user_number'],
            'last_login_at'     => $user['last_login_at'],
            'created_at'        => $user['created_at'],
            'status'            => $user['status'],
        ];
    }

    protected function panelSettings(): array
    {
        return $this->db
            ->query("SELECT `key`, value FROM system_settings WHERE `key` IN ('library_name','library_logo','library_favicon')")
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
