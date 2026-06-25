<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Entity;

use App\Transaction\Domain\Enum\TransactionStatus;
use App\Transaction\Domain\Exception\SelfTransferException;
use App\Wallet\Domain\ValueObject\Money;
use DateTimeImmutable;

final class Transaction
{
    public function __construct(
        private readonly string $id,
        private readonly string $payerId,
        private readonly string $payeeId,
        private readonly Money $amount,
        private TransactionStatus $status,
        private ?DateTimeImmutable $createdAt = null,
    ) {
        if ($this->payerId === $this->payeeId) {
            throw new SelfTransferException($this->payerId);
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function payerId(): string
    {
        return $this->payerId;
    }

    public function payeeId(): string
    {
        return $this->payeeId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): TransactionStatus
    {
        return $this->status;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsCompleted(): void
    {
        $this->status = TransactionStatus::COMPLETED;
    }

    public function markAsFailed(): void
    {
        $this->status = TransactionStatus::FAILED;
    }
}
