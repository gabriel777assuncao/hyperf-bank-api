<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserAlreadyExistsException extends AbstractWithContextException implements DomainException
{
    public static function document(string $document): self
    {
        return new self(sprintf('A user with document "%s" already exists.', $document));
    }

    public static function email(string $email): self
    {
        return new self(sprintf('A user with email "%s" already exists.', $email));
    }
}
