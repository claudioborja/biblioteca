<?php
// app/Enums/Role.php
declare(strict_types=1);

namespace Enums;

enum Role: string
{
    case Admin     = 'admin';
    case Librarian = 'librarian';
    case Teacher   = 'teacher';
    case User      = 'user';
    case Guest     = 'guest';
}
