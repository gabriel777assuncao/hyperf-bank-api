<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class TransferNotAuthorizedException extends RuntimeException implements DomainException
{
    public function __construct(string $message = 'Transfer not authorized by external authorizer.')
    {
        parent::__construct($message);
    }
}
