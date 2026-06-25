<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Domain\Enum;

use App\Transaction\Domain\Enum\TransactionStatus;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * @internal
 * @covers \App\Transaction\Domain\Enum\TransactionStatus
 */
final class TransactionStatusTest extends TestCase
{
    public function test_has_pending_case(): void
    {
        $this->assertSame('pending', TransactionStatus::PENDING->value);
    }

    public function test_has_completed_case(): void
    {
        $this->assertSame('completed', TransactionStatus::COMPLETED->value);
    }

    public function test_has_failed_case(): void
    {
        $this->assertSame('failed', TransactionStatus::FAILED->value);
    }

    public function test_from_completed_returns_completed(): void
    {
        $this->assertSame(TransactionStatus::COMPLETED, TransactionStatus::from('completed'));
    }

    public function test_from_invalid_throws_value_error(): void
    {
        $this->expectException(ValueError::class);

        TransactionStatus::from('invalid');
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, TransactionStatus::cases());
    }
}
