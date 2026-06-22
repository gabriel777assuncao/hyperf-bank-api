<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use App\Wallet\Domain\ValueObject\Money;
use RuntimeException;

final class InsufficientBalanceException extends RuntimeException implements DomainException
{
    public function __construct(Money $required, Money $available)
    {
        parent::__construct(sprintf(
            'Insufficient balance: required %d cents, available %d cents.',
            $required->toCents(),
            $available->toCents(),
        ));
    }
}
