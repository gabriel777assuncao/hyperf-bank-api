<?php

declare(strict_types=1);

namespace App\Wallet\Domain\ValueObject;

use InvalidArgumentException;

final class Money
{
    public function __construct(
        private readonly int $cents,
        private readonly bool $validate = true,
    ) {
        if ($validate && $cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents, validate: false);
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function lessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function toCents(): int
    {
        return $this->cents;
    }
}
