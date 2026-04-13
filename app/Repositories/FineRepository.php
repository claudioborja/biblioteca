<?php
// app/Repositories/FineRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class FineRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement FineRepository methods
}
