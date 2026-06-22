<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use RuntimeException;

final class UserNotFoundException extends RuntimeException implements DomainException
{
    public static function byDocument(string $document): self
    {
        return new self(sprintf('User with document "%s" not found.', $document));
    }

    public static function byId(string $id): self
    {
        return new self(sprintf('User with id "%s" not found.', $id));
    }

    public static function byEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" not found.', $email));
    }
}
