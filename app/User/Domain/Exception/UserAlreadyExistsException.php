<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use RuntimeException;

final class UserAlreadyExistsException extends RuntimeException implements DomainException
{
    public static function document(string $document): self
    {
        return new self(sprintf('A user with document "%s" already exists.', $document));
    }

    public static function email(string $email): self
    {
        return new self(sprintf('A user with email "%s" already exists.', $email));
    }

    public static function conflict(): self
    {
        return new self('User with this document or email already exists.');
    }
}
