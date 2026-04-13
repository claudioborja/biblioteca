<?php
// app/Repositories/ReservationRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class ReservationRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // TODO: Implement ReservationRepository methods
}
