<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum UserType: string
{
    case NORMAL = 'NORMAL';
    case SHOPKEEPER = 'SHOPKEEPER';
}
