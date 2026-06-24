<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Enum;

enum TransactionStatus: string
{
    case PENDING = 'pending';

    case COMPLETED = 'completed';

    case FAILED = 'failed';
}
