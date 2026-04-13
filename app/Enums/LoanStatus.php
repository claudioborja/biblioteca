<?php
// app/Enums/LoanStatus.php
declare(strict_types=1);

namespace Enums;

enum LoanStatus: string
{
    case Active   = 'active';
    case Returned = 'returned';
    case Overdue  = 'overdue';
    case Lost     = 'lost';
}
