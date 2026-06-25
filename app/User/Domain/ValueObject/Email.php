<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $raw)
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Email is too long.');
        }

        if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format.');
        }

        $this->value = mb_strtolower($trimmed);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
