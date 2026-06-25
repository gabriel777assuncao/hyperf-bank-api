<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Cpf;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\ValueObject\Cpf
 */
final class CpfTest extends TestCase
{
    public static function validCpfProvider(): Generator
    {
        yield 'formatted with dots and dash' => ['529.982.247-25', '52998224725', '529.982.247-25'];
        yield 'raw digits' => ['52998224725', '52998224725', '529.982.247-25'];
        yield 'formatted second' => ['280.012.389-38', '28001238938', '280.012.389-38'];
        yield 'formatted third' => ['764.252.700-47', '76425270047', '764.252.700-47'];
        yield 'raw second' => ['28001238938', '28001238938', '280.012.389-38'];
    }

    #[DataProvider('validCpfProvider')]
    public function test_valid_cpf_from_formatted_and_raw(string $input, string $expectedDigits, string $expectedFormatted): void
    {
        $cpf = new Cpf($input);

        $this->assertSame($expectedDigits, $cpf->toString());
        $this->assertSame($expectedFormatted, $cpf->formatted());
    }

    public static function invalidCpfProvider(): Generator
    {
        yield 'wrong check digits' => ['123.456.789-00', 'Invalid CPF number.'];
        yield 'all equal digits' => ['111.111.111-11', 'Invalid CPF number.'];
        yield 'all zeros' => ['000.000.000-00', 'Invalid CPF number.'];
        yield 'less than 11 digits' => ['123', 'CPF must have 11 digits.'];
        yield 'more than 11 digits' => ['1234567890123', 'CPF must have 11 digits.'];
        yield 'empty string' => ['', 'CPF must have 11 digits.'];
        yield 'only whitespace' => ['           ', 'CPF must have 11 digits.'];
        yield 'letters mixed' => ['529.982.247-AB', 'CPF must have 11 digits.'];
        yield 'special characters only' => ['...---...---', 'CPF must have 11 digits.'];
        yield 'null bytes injected' => ["\0\0\0\0\0\0\0\0\0\0\0", 'CPF must have 11 digits.'];
    }

    #[DataProvider('invalidCpfProvider')]
    public function test_invalid_cpf_throws_exception(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Cpf($input);
    }

    public static function cpfDigitsProvider(): Generator
    {
        yield 'only digits 1' => ['52998224725'];
        yield 'only digits 2' => ['28001238938'];
        yield 'only digits 3' => ['76425270047'];
    }

    #[DataProvider('cpfDigitsProvider')]
    public function test_valid_cpf_only_digits(string $input): void
    {
        $cpf = new Cpf($input);

        $this->assertSame($input, $cpf->toString());
    }

    public static function cpfFormattedProvider(): Generator
    {
        yield 'cpf 1' => ['52998224725', '529.982.247-25'];
        yield 'cpf 2' => ['28001238938', '280.012.389-38'];
        yield 'cpf 3' => ['76425270047', '764.252.700-47'];
    }

    #[DataProvider('cpfFormattedProvider')]
    public function test_cpf_formatted_output(string $digits, string $expectedFormatted): void
    {
        $cpf = new Cpf($digits);

        $this->assertSame($expectedFormatted, $cpf->formatted());
    }

    public function test_cpf_equality(): void
    {
        $a = new Cpf('529.982.247-25');
        $b = new Cpf('52998224725');
        $c = new Cpf('280.012.389-38');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
        $this->assertTrue($a->equals($a));
    }
}
