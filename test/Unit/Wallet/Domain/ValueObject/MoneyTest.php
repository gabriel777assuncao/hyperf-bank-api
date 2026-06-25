<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Wallet\Domain\ValueObject;

use App\Wallet\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\Wallet\Domain\ValueObject\Money
 */
final class MoneyTest extends TestCase
{
    public function test_create_valid_money(): void
    {
        $money = new Money(100);

        $this->assertSame(100, $money->toCents());
    }

    public function test_create_zero_money(): void
    {
        $money = new Money(0);

        $this->assertSame(0, $money->toCents());
    }

    public function test_create_negative_money_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money cannot be negative.');

        new Money(-1);
    }

    public function test_add_returns_sum(): void
    {
        $result = (new Money(100))->add(new Money(50));

        $this->assertSame(150, $result->toCents());
    }

    public function test_subtract_returns_difference(): void
    {
        $result = (new Money(100))->subtract(new Money(30));

        $this->assertSame(70, $result->toCents());
    }

    public function test_subtract_producing_negative_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money cannot be negative.');

        (new Money(50))->subtract(new Money(100));
    }

    public function test_greater_than_or_equal_returns_true_when_equal(): void
    {
        $this->assertTrue((new Money(100))->greaterThanOrEqual(new Money(100)));
    }

    public function test_greater_than_or_equal_returns_false_when_less(): void
    {
        $this->assertFalse((new Money(100))->greaterThanOrEqual(new Money(101)));
    }

    public function test_less_than_returns_true_when_less(): void
    {
        $this->assertTrue((new Money(50))->lessThan(new Money(100)));
    }

    public function test_less_than_returns_false_when_equal(): void
    {
        $this->assertFalse((new Money(100))->lessThan(new Money(100)));
    }

    public function test_equals_returns_true_when_same_value(): void
    {
        $this->assertTrue((new Money(100))->equals(new Money(100)));
    }

    public function test_equals_returns_false_when_different_value(): void
    {
        $this->assertFalse((new Money(100))->equals(new Money(99)));
    }

    public function test_add_returns_new_instance_and_original_unchanged(): void
    {
        $original = new Money(100);
        $result = $original->add(new Money(50));

        $this->assertSame(100, $original->toCents());
        $this->assertSame(150, $result->toCents());
        $this->assertNotSame($original, $result);
    }

    public function test_subtract_returns_new_instance_and_original_unchanged(): void
    {
        $original = new Money(100);
        $result = $original->subtract(new Money(30));

        $this->assertSame(100, $original->toCents());
        $this->assertSame(70, $result->toCents());
        $this->assertNotSame($original, $result);
    }
}
