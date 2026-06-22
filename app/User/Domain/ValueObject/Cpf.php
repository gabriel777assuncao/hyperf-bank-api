<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Cpf
{
    private string $value;

    public function __construct(string $raw)
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (mb_strlen($digits) !== 11) {
            throw new InvalidArgumentException('CPF must have 11 digits.');
        }

        if (! $this->isValid($digits)) {
            throw new InvalidArgumentException('Invalid CPF number.');
        }

        $this->value = $digits;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function formatted(): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($this->value, 0, 3),
            substr($this->value, 3, 3),
            substr($this->value, 6, 3),
            substr($this->value, 9, 2),
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValid(string $digits): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        $weightsList = [
            [10, 9, 8, 7, 6, 5, 4, 3, 2],
            [11, 10, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weightsList as $position => $weights) {
            $sum = 0;

            foreach ($weights as $i => $weight) {
                $sum += (int) $digits[$i] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $digits[9 + $position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
