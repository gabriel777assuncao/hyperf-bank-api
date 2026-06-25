<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class UnauthorizedTransferException extends RuntimeException implements DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct(sprintf('User "%s" (shopkeeper) is not allowed to send transfers.', $userId));
    }
}
