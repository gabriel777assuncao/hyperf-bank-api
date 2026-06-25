<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Cnpj;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\ValueObject\Cnpj
 */
final class CnpjTest extends TestCase
{
    public static function validCnpjProvider(): Generator
    {
        yield 'formatted with dots slash dash' => ['11.222.333/0001-81', '11222333000181', '11.222.333/0001-81'];
        yield 'raw digits' => ['11222333000181', '11222333000181', '11.222.333/0001-81'];
        yield 'formatted second' => ['60.765.412/0001-44', '60765412000144', '60.765.412/0001-44'];
        yield 'raw second' => ['60765412000144', '60765412000144', '60.765.412/0001-44'];
        yield 'formatted third' => ['14.265.909/0001-86', '14265909000186', '14.265.909/0001-86'];
    }

    #[DataProvider('validCnpjProvider')]
    public function test_valid_cnpj_from_formatted_and_raw(string $input, string $expectedDigits, string $expectedFormatted): void
    {
        $cnpj = new Cnpj($input);

        $this->assertSame($expectedDigits, $cnpj->toString());
        $this->assertSame($expectedFormatted, $cnpj->formatted());
    }

    public static function invalidCnpjProvider(): Generator
    {
        yield 'wrong check digits' => ['11222333000100', 'Invalid CNPJ number.'];
        yield 'all equal digits' => ['11111111111111', 'Invalid CNPJ number.'];
        yield 'all zeros' => ['00000000000000', 'Invalid CNPJ number.'];
        yield 'less than 14 digits' => ['123', 'CNPJ must have 14 digits.'];
        yield 'more than 14 digits' => ['123456789012345', 'CNPJ must have 14 digits.'];
        yield 'empty string' => ['', 'CNPJ must have 14 digits.'];
        yield 'only whitespace' => ['              ', 'CNPJ must have 14 digits.'];
        yield 'letters mixed' => ['11.222.333/0001-AB', 'CNPJ must have 14 digits.'];
        yield 'only slashes and dots' => ['../../....-..', 'CNPJ must have 14 digits.'];
    }

    #[DataProvider('invalidCnpjProvider')]
    public function test_invalid_cnpj_throws_exception(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Cnpj($input);
    }

    public static function cnpjDigitsProvider(): Generator
    {
        yield 'digits 1' => ['11222333000181'];
        yield 'digits 2' => ['60765412000144'];
        yield 'digits 3' => ['14265909000186'];
    }

    #[DataProvider('cnpjDigitsProvider')]
    public function test_valid_cnpj_only_digits(string $input): void
    {
        $cnpj = new Cnpj($input);

        $this->assertSame($input, $cnpj->toString());
    }

    public static function cnpjFormattedProvider(): Generator
    {
        yield 'cnpj 1' => ['11222333000181', '11.222.333/0001-81'];
        yield 'cnpj 2' => ['60765412000144', '60.765.412/0001-44'];
        yield 'cnpj 3' => ['14265909000186', '14.265.909/0001-86'];
    }

    #[DataProvider('cnpjFormattedProvider')]
    public function test_cnpj_formatted_output(string $digits, string $expectedFormatted): void
    {
        $cnpj = new Cnpj($digits);

        $this->assertSame($expectedFormatted, $cnpj->formatted());
    }

    public function test_cnpj_equality(): void
    {
        $a = new Cnpj('11.222.333/0001-81');
        $b = new Cnpj('11222333000181');
        $c = new Cnpj('60765412000144');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertTrue($a->equals($a));
    }
}
