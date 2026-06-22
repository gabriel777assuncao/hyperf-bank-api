<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;
use RuntimeException;

final class Password
{
    private string $hash;

    public function __construct(string $plain)
    {
        $trimmed = trim($plain);

        if (mb_strlen($trimmed) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        $hash = password_hash($trimmed, PASSWORD_BCRYPT);

        if ($hash === false) {
            throw new RuntimeException('Failed to hash password.');
        }

        $this->hash = $hash;
    }

    public static function fromHash(string $hash): self
    {
        $instance = new self('placeholder-for-hash');
        $instance->hash = $hash;

        return $instance;
    }

    public function verify(string $plain): bool
    {
        return password_verify($plain, $this->hash);
    }

    public function toString(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }
}