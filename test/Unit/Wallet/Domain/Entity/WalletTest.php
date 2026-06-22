<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Wallet\Domain\Entity;

use App\Wallet\Domain\Entity\Wallet;
use App\Wallet\Domain\Exception\InsufficientBalanceException;
use App\Wallet\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\Wallet\Domain\Entity\Wallet
 */
final class WalletTest extends TestCase
{
    public function test_credit_increases_balance(): void
    {
        $wallet = new Wallet('wallet-1', 'user-1', new Money(100));

        $wallet->credit(new Money(100));

        $this->assertSame(200, $wallet->balance()->toCents());
    }

    public function test_debit_decreases_balance(): void
    {
        $wallet = new Wallet('wallet-1', 'user-1', new Money(100));

        $wallet->debit(new Money(50));

        $this->assertSame(50, $wallet->balance()->toCents());
    }

    public function test_debit_insufficient_balance_throws_exception(): void
    {
        $wallet = new Wallet('wallet-1', 'user-1', new Money(100));

        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage('Insufficient balance: required 150 cents, available 100 cents.');

        $wallet->debit(new Money(150));
    }

    public function test_debit_exact_balance_results_in_zero(): void
    {
        $wallet = new Wallet('wallet-1', 'user-1', new Money(100));

        $wallet->debit(new Money(100));

        $this->assertSame(0, $wallet->balance()->toCents());
    }

    public function test_getters_return_constructor_values(): void
    {
        $wallet = new Wallet('wallet-id', 'user-id', new Money(0));

        $this->assertSame('wallet-id', $wallet->id());
        $this->assertSame('user-id', $wallet->userId());
        $this->assertSame(0, $wallet->balance()->toCents());
    }
}
