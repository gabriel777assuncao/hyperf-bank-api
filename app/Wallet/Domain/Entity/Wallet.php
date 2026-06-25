<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Entity;

use App\Wallet\Domain\Exception\InsufficientBalanceException;
use App\Wallet\Domain\ValueObject\Money;

final class Wallet
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private Money $balance,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function debit(Money $amount): void
    {
        if ($this->balance->lessThan($amount)) {
            throw new InsufficientBalanceException($amount, $this->balance);
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function credit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }
}
