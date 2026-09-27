<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Domain\Enum;

use App\Transaction\Domain\Enum\OutboxEventStatus;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * @internal
 * @covers \App\Transaction\Domain\Enum\OutboxEventStatus
 */
final class OutboxEventStatusTest extends TestCase
{
    public function test_has_pending_case(): void
    {
        $this->assertSame('pending', OutboxEventStatus::PENDING->value);
    }

    public function test_has_published_case(): void
    {
        $this->assertSame('published', OutboxEventStatus::PUBLISHED->value);
    }

    public function test_has_failed_case(): void
    {
        $this->assertSame('failed', OutboxEventStatus::FAILED->value);
    }

    public function test_from_published_returns_published(): void
    {
        $this->assertSame(OutboxEventStatus::PUBLISHED, OutboxEventStatus::from('published'));
    }

    public function test_from_invalid_throws_value_error(): void
    {
        $this->expectException(ValueError::class);

        OutboxEventStatus::from('invalid');
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, OutboxEventStatus::cases());
    }
}
