<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Cnpj
{
    private string $value;

    public function __construct(string $raw)
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (mb_strlen($digits) !== 14) {
            throw new InvalidArgumentException('CNPJ must have 14 digits.');
        }

        if (! $this->isValid($digits)) {
            throw new InvalidArgumentException('Invalid CNPJ number.');
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
            '%s.%s.%s/%s-%s',
            substr($this->value, 0, 2),
            substr($this->value, 2, 3),
            substr($this->value, 5, 3),
            substr($this->value, 8, 4),
            substr($this->value, 12, 2),
        );
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValid(string $digits): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weightsList = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weightsList as $position => $weights) {
            $sum = 0;

            foreach ($weights as $i => $weight) {
                $sum += (int) $digits[$i] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $digits[12 + $position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
