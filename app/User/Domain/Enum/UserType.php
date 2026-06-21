<?php

declare(strict_types=1);

namespace Domain\Enum;

enum UserType: string
{
    case COMMON = 'COMMON';
    case MERCHANT = 'MERCHANT';
}
