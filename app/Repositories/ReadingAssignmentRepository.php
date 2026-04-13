<?php
// app/Repositories/ReadingAssignmentRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ReadingAssignmentRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement ReadingAssignmentRepository methods
}
