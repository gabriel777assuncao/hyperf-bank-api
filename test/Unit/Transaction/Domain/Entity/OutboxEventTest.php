<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Domain\Entity;

use App\Transaction\Domain\Entity\{OutboxEvent, Transaction};
use App\Transaction\Domain\Enum\{OutboxEventStatus, TransactionStatus};
use App\Wallet\Domain\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Domain\Entity\OutboxEvent
 */
final class OutboxEventTest extends TestCase
{
    public function test_for_completed_transfer_builds_pending_event_with_payload(): void
    {
        $transaction = $this->makeCompletedTransaction();

        $event = OutboxEvent::forCompletedTransfer('event-1', $transaction);

        $this->assertSame('event-1', $event->id());
        $this->assertSame('tx-1', $event->aggregateId());
        $this->assertSame(OutboxEventStatus::PENDING, $event->status());
        $this->assertNull($event->publishedAt());
        $this->assertSame(0, $event->tries());
        $this->assertNull($event->nextRetryAt());
        $this->assertSame([
            'transaction_id' => 'tx-1',
            'payer_id' => 'payer-1',
            'payee_id' => 'payee-1',
            'value' => 10000,
            'status' => 'completed',
        ], $event->payload());
    }

    public function test_mark_as_published_changes_status_and_sets_published_at(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        $event->markAsPublished($now);

        $this->assertSame(OutboxEventStatus::PUBLISHED, $event->status());
        $this->assertSame($now, $event->publishedAt());
        $this->assertNull($event->nextRetryAt());
    }

    public function test_mark_as_published_defaults_to_current_time(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());

        $event->markAsPublished();

        $this->assertInstanceOf(DateTimeImmutable::class, $event->publishedAt());
    }

    public function test_mark_as_failed_changes_status_without_published_at(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());

        $event->markAsFailed();

        $this->assertSame(OutboxEventStatus::FAILED, $event->status());
        $this->assertNull($event->publishedAt());
        $this->assertNull($event->nextRetryAt());
    }

    public function test_register_failure_schedules_retry_with_exponential_backoff(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        $event->registerFailure($now, 5, 60);

        $this->assertSame(OutboxEventStatus::PENDING, $event->status());
        $this->assertSame(1, $event->tries());
        $this->assertSame('2026-01-01 00:00:02', $event->nextRetryAt()?->format('Y-m-d H:i:s'));

        $event->registerFailure($now, 5, 60);

        $this->assertSame(2, $event->tries());
        $this->assertSame('2026-01-01 00:00:04', $event->nextRetryAt()?->format('Y-m-d H:i:s'));

        $event->registerFailure($now, 5, 60);

        $this->assertSame(3, $event->tries());
        $this->assertSame('2026-01-01 00:00:08', $event->nextRetryAt()?->format('Y-m-d H:i:s'));
    }

    public function test_register_failure_caps_delay_at_max_delay_seconds(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        $event->registerFailure($now, 10, 5);
        $this->assertSame('2026-01-01 00:00:02', $event->nextRetryAt()?->format('Y-m-d H:i:s'));

        $event->registerFailure($now, 10, 5);
        $this->assertSame('2026-01-01 00:00:04', $event->nextRetryAt()?->format('Y-m-d H:i:s'));

        $event->registerFailure($now, 10, 5);
        $this->assertSame('2026-01-01 00:00:05', $event->nextRetryAt()?->format('Y-m-d H:i:s'));
    }

    public function test_register_failure_marks_as_failed_when_max_tries_reached(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        for ($attempt = 1; $attempt < 5; ++$attempt) {
            $event->registerFailure($now, 5, 60);
            $this->assertSame(OutboxEventStatus::PENDING, $event->status());
        }

        $event->registerFailure($now, 5, 60);

        $this->assertSame(OutboxEventStatus::FAILED, $event->status());
        $this->assertSame(5, $event->tries());
        $this->assertNull($event->nextRetryAt());
    }

    public function test_created_at_defaults_to_null(): void
    {
        $event = OutboxEvent::forCompletedTransfer('event-1', $this->makeCompletedTransaction());

        $this->assertNull($event->createdAt());
    }

    private function makeCompletedTransaction(): Transaction
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer-1',
            payeeId: 'payee-1',
            amount: new Money(10000),
            status: TransactionStatus::PENDING,
        );

        $transaction->markAsCompleted();

        return $transaction;
    }
}
