<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Domain\Entity;

use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Enum\TransactionStatus;
use App\Wallet\Domain\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Domain\Entity\Transaction
 */
final class TransactionTest extends TestCase
{
    public function test_getters_return_constructor_values(): void
    {
        $amount = new Money(10000);
        $createdAt = new DateTimeImmutable('2026-01-01 00:00:00');
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer-uuid',
            payeeId: 'payee-uuid',
            amount: $amount,
            status: TransactionStatus::COMPLETED,
            createdAt: $createdAt,
        );

        $this->assertSame('tx-1', $transaction->id());
        $this->assertSame('payer-uuid', $transaction->payerId());
        $this->assertSame('payee-uuid', $transaction->payeeId());
        $this->assertSame($amount, $transaction->amount());
        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status());
        $this->assertSame($createdAt, $transaction->createdAt());
    }

    public function test_amount_to_cents(): void
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer',
            payeeId: 'payee',
            amount: new Money(5000),
            status: TransactionStatus::COMPLETED,
        );

        $this->assertSame(5000, $transaction->amount()->toCents());
    }

    public function test_default_status_is_completed(): void
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer',
            payeeId: 'payee',
            amount: new Money(100),
            status: TransactionStatus::COMPLETED,
        );

        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status());
    }

    public function test_mark_as_completed_changes_status(): void
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer',
            payeeId: 'payee',
            amount: new Money(100),
            status: TransactionStatus::PENDING,
        );

        $transaction->markAsCompleted();

        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status());
    }

    public function test_mark_as_failed_changes_status(): void
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer',
            payeeId: 'payee',
            amount: new Money(100),
            status: TransactionStatus::COMPLETED,
        );

        $transaction->markAsFailed();

        $this->assertSame(TransactionStatus::FAILED, $transaction->status());
    }

    public function test_created_at_is_nullable(): void
    {
        $transaction = new Transaction(
            id: 'tx-1',
            payerId: 'payer',
            payeeId: 'payee',
            amount: new Money(100),
            status: TransactionStatus::COMPLETED,
        );

        $this->assertNull($transaction->createdAt());
    }
}
