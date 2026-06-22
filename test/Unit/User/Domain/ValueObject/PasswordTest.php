<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Password;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\ValueObject\Password
 */
final class PasswordTest extends TestCase
{
    public static function validPasswordProvider(): Generator
    {
        yield 'minimum length' => ['12345678'];
        yield 'longer password' => ['password123'];
        yield 'with special chars' => ['A!b@C#d$'];
        yield 'with unicode' => ['aÇão日本語😀'];
        yield 'exactly 8 chars' => ['abcd1234'];
    }

    #[DataProvider('validPasswordProvider')]
    public function test_valid_password_is_hashed(string $plain): void
    {
        $password = new Password($plain);

        $this->assertStringStartsWith('$2y$', $password->toString());
        $this->assertNotSame($plain, $password->toString());
    }

    public static function shortPasswordProvider(): Generator
    {
        yield 'empty string' => [''];
        yield 'single char' => ['1'];
        yield 'seven chars' => ['1234567'];
        yield 'whitespace trimmed to short' => ['   ab   '];
        yield 'unicode short' => ['日本語'];
    }

    #[DataProvider('shortPasswordProvider')]
    public function test_too_short_password_throws_exception(string $plain): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters.');

        new Password($plain);
    }

    public function test_password_verify_correct(): void
    {
        $password = new Password('password123');

        $this->assertTrue($password->verify('password123'));
    }

    public function test_password_verify_incorrect(): void
    {
        $password = new Password('password123');

        $this->assertFalse($password->verify('wrongpassword'));
    }

    public function test_password_verify_case_sensitive(): void
    {
        $password = new Password('Password123');

        $this->assertTrue($password->verify('Password123'));
        $this->assertFalse($password->verify('password123'));
    }

    public static function hashProvider(): Generator
    {
        yield 'short password' => ['12345678'];
        yield 'long password' => ['password123'];
        yield 'special chars' => ['A!b@C#d$'];
    }

    #[DataProvider('hashProvider')]
    public function test_password_from_hash_reconstitutes(string $plain): void
    {
        $original = new Password($plain);
        $reconstituted = Password::fromHash($original->toString());

        $this->assertTrue($reconstituted->verify($plain));
        $this->assertTrue($original->equals($reconstituted));
    }

    public function test_password_equality(): void
    {
        $a = new Password('password123');
        $b = Password::fromHash($a->toString());
        $c = new Password('other1234');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
