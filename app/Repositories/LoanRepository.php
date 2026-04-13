<?php
// app/Repositories/LoanRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class LoanRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement LoanRepository methods
}
