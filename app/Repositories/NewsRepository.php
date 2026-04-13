<?php
// app/Repositories/NewsRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class NewsRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement NewsRepository methods
}
