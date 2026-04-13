<?php
// app/Enums/UserStatus.php
declare(strict_types=1);

namespace Enums;

enum UserStatus: string
{
    case Active    = 'active';
    case Suspended = 'suspended';
    case Blocked   = 'blocked';
    case Inactive  = 'inactive';
}
