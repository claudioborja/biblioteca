<?php
// app/Enums/FineStatus.php
declare(strict_types=1);

namespace Enums;

enum FineStatus: string
{
    case Pending       = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid          = 'paid';
    case Waived        = 'waived';
}
