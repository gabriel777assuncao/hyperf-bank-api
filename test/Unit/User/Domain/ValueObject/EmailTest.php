<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\ValueObject\Email
 */
final class EmailTest extends TestCase
{
    public static function validEmailProvider(): Generator
    {
        yield 'simple email' => ['a@b.c', 'a@b.c'];
        yield 'with tag' => ['user+tag@domain.com', 'user+tag@domain.com'];
        yield 'with subdomain' => ['user@sub.domain.com', 'user@sub.domain.com'];
        yield 'uppercase' => ['USER@EXAMPLE.COM', 'user@example.com'];
        yield 'mixed case' => ['JoAo@Example.Com', 'joao@example.com'];
        yield 'with numbers' => ['user123@domain456.com', 'user123@domain456.com'];
        yield 'with dots in local' => ['first.last@domain.com', 'first.last@domain.com'];
        yield 'trimmed whitespace' => ['  maria@example.com  ', 'maria@example.com'];
    }

    #[DataProvider('validEmailProvider')]
    public function test_valid_email(string $input, string $expected): void
    {
        $email = new Email($input);

        $this->assertSame($expected, $email->toString());
    }

    public static function invalidEmailProvider(): Generator
    {
        yield 'no at sign' => ['not-an-email', 'Invalid email format.'];
        yield 'empty string' => ['', 'Email cannot be empty.'];
        yield 'only whitespace' => ['   ', 'Email cannot be empty.'];
        yield 'at sign only' => ['@', 'Invalid email format.'];
        yield 'no domain' => ['user@', 'Invalid email format.'];
        yield 'no local part' => ['@domain.com', 'Invalid email format.'];
        yield 'dot at end' => ['user@domain.', 'Invalid email format.'];
        yield 'double dot' => ['user@domain..com', 'Invalid email format.'];
        yield 'spaces inside' => ['user name@domain.com', 'Invalid email format.'];
    }

    #[DataProvider('invalidEmailProvider')]
    public function test_invalid_email_throws_exception(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Email($input);
    }

    public static function emailCaseProvider(): Generator
    {
        yield 'all caps' => ['HELLO@WORLD.COM', 'hello@world.com'];
        yield 'mixed' => ['Hello@World.Com', 'hello@world.com'];
        yield 'already lowercase' => ['hello@world.com', 'hello@world.com'];
    }

    #[DataProvider('emailCaseProvider')]
    public function test_email_is_lowercased(string $input, string $expected): void
    {
        $email = new Email($input);

        $this->assertSame($expected, $email->toString());
    }

    public function test_email_equality(): void
    {
        $a = new Email('JOao@Example.com');
        $b = new Email('joao@example.com');
        $c = new Email('maria@example.com');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertTrue($a->equals($a));
    }
}
