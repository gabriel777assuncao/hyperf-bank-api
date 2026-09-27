<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Enum;

enum OutboxEventStatus: string
{
    case PENDING = 'pending';

    case PUBLISHED = 'published';

    case FAILED = 'failed';
}
