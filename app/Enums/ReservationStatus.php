<?php
// app/Enums/ReservationStatus.php
declare(strict_types=1);

namespace Enums;

enum ReservationStatus: string
{
    case Waiting   = 'waiting';
    case Notified  = 'notified';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired   = 'expired';
}
