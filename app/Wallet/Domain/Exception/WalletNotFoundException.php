<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class WalletNotFoundException extends RuntimeException implements DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
