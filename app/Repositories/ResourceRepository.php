<?php
// app/Repositories/ResourceRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ResourceRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement BookRepository methods
}
