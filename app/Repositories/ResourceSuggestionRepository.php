<?php
// app/Repositories/ResourceSuggestionRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ResourceSuggestionRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement ResourceSuggestionRepository methods
}
