<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class UserAlreadyExistsException extends RuntimeException implements DomainException
{
    public function __construct(string $message = 'User with this document or email already exists.')
    {
        parent::__construct($message);
    }
}
