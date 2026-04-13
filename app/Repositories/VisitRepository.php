<?php
// app/Repositories/VisitRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class VisitRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement VisitRepository methods
}
