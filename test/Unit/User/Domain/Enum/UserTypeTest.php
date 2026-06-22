<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\Enum;

use App\User\Domain\Enum\UserType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\Enum\UserType
 */
final class UserTypeTest extends TestCase
{
    public static function typeCasesProvider(): Generator
    {
        yield 'normal' => [UserType::NORMAL, 'NORMAL'];
        yield 'shopkeeper' => [UserType::SHOPKEEPER, 'SHOPKEEPER'];
    }

    #[DataProvider('typeCasesProvider')]
    public function test_user_type_cases(UserType $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
        $this->assertSame($expectedValue, $case->name);
    }

    public static function validTypeStringProvider(): Generator
    {
        yield 'normal string' => ['NORMAL', UserType::NORMAL];
        yield 'shopkeeper string' => ['SHOPKEEPER', UserType::SHOPKEEPER];
    }

    #[DataProvider('validTypeStringProvider')]
    public function test_user_type_from_valid_string(string $input, UserType $expected): void
    {
        $this->assertSame($expected, UserType::from($input));
    }

    public static function invalidTypeStringProvider(): Generator
    {
        yield 'old common value' => ['COMMON'];
        yield 'lowercase normal' => ['normal'];
        yield 'lowercase shopkeeper' => ['shopkeeper'];
        yield 'old merchant value' => ['MERCHANT'];
        yield 'empty string' => [''];
        yield 'admin' => ['ADMIN'];
        yield 'random' => ['RANDOM_TYPE'];
    }

    #[DataProvider('invalidTypeStringProvider')]
    public function test_user_type_from_invalid_string_throws(string $input): void
    {
        $this->expectException(ValueError::class);

        UserType::from($input);
    }

    public function test_user_type_try_from_invalid_returns_null(): void
    {
        $this->assertNull(UserType::tryFrom('COMMON'));
        $this->assertNull(UserType::tryFrom(''));
        $this->assertNull(UserType::tryFrom('RANDOM'));
    }
}
