<?php
// app/Repositories/TeacherGroupRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class TeacherGroupRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement TeacherGroupRepository methods
}
