<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use RuntimeException;

final class InvalidCredentialsException extends RuntimeException implements DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
