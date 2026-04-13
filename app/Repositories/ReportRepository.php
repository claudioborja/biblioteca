<?php
// app/Repositories/ReportRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ReportRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement ReportRepository methods
}
