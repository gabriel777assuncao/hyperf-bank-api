<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class UserNotFoundException extends RuntimeException implements DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
