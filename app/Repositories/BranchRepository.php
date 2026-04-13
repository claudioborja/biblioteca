<?php
// app/Repositories/BranchRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class BranchRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement BranchRepository methods
}
