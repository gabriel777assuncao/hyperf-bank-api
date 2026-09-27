<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Entity;

use App\Transaction\Domain\Enum\OutboxEventStatus;
use DateTimeImmutable;

final class OutboxEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $id,
        private readonly string $aggregateId,
        private array $payload,
        private OutboxEventStatus $status,
        private ?DateTimeImmutable $publishedAt = null,
        private readonly ?DateTimeImmutable $createdAt = null,
        private int $tries = 0,
        private ?DateTimeImmutable $nextRetryAt = null,
    ) {
    }

    public static function forCompletedTransfer(string $id, Transaction $transaction): self
    {
        return new self(
            id: $id,
            aggregateId: $transaction->id(),
            payload: [
                'transaction_id' => $transaction->id(),
                'payer_id' => $transaction->payerId(),
                'payee_id' => $transaction->payeeId(),
                'value' => $transaction->amount()->toCents(),
                'status' => $transaction->status()->value,
            ],
            status: OutboxEventStatus::PENDING,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function status(): OutboxEventStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function tries(): int
    {
        return $this->tries;
    }

    public function nextRetryAt(): ?DateTimeImmutable
    {
        return $this->nextRetryAt;
    }

    public function markAsPublished(?DateTimeImmutable $now = null): void
    {
        $this->status = OutboxEventStatus::PUBLISHED;
        $this->publishedAt = $now ?? new DateTimeImmutable();
        $this->nextRetryAt = null;
    }

    public function markAsFailed(): void
    {
        $this->status = OutboxEventStatus::FAILED;
        $this->nextRetryAt = null;
    }

    /**
     * Registers a publish failure and schedules the next attempt using
     * exponential backoff (2^tries seconds, capped at $maxDelaySeconds).
     *
     * When the attempt count reaches $maxTries the event is terminally failed.
     */
    public function registerFailure(DateTimeImmutable $now, int $maxTries, int $maxDelaySeconds): void
    {
        ++$this->tries;

        if ($this->tries >= $maxTries) {
            $this->markAsFailed();

            return;
        }

        $delay = min(2 ** $this->tries, $maxDelaySeconds);
        $this->nextRetryAt = $now->modify(sprintf('+%d seconds', $delay));
    }
}
