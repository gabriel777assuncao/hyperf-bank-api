<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\ValueObject\{Cnpj, Cpf, Email, Password};
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Generator;

/**
 * @internal
 * @covers \App\User\Domain\Entity\User
 */
final class UserTest extends TestCase
{
    public function test_create_user_with_required_fields(): void
    {
        $id = '550e8400-e29b-41d4-a716-446655440000';
        $email = new Email('test@example.com');
        $password = new Password('password123');

        $user = new User(
            id: $id,
            fullName: 'John Doe',
            email: $email,
            password: $password,
            type: UserType::NORMAL,
        );

        $this->assertSame($id, $user->id());
        $this->assertSame('John Doe', $user->fullName());
        $this->assertSame('test@example.com', $user->email()->toString());
        $this->assertTrue($password->equals($user->password()));
        $this->assertSame(UserType::NORMAL, $user->type());
        $this->assertNull($user->cpf());
        $this->assertNull($user->cnpj());
        $this->assertNull($user->createdAt());
        $this->assertNull($user->updatedAt());
    }

    public function test_create_user_with_cpf(): void
    {
        $cpf = new Cpf('52998224725');

        $user = new User(
            id: 'id-1',
            fullName: 'Jane',
            email: new Email('jane@test.com'),
            password: new Password('password123'),
            type: UserType::NORMAL,
            cpf: $cpf,
        );

        $this->assertNotNull($user->cpf());
        $this->assertSame('52998224725', $user->cpf()->toString());
        $this->assertNull($user->cnpj());
        $this->assertSame('529.982.247-25', $user->document());
    }

    public function test_create_user_with_cnpj(): void
    {
        $cnpj = new Cnpj('11222333000181');

        $user = new User(
            id: 'id-2',
            fullName: 'Acme Corp',
            email: new Email('acme@test.com'),
            password: new Password('password123'),
            type: UserType::SHOPKEEPER,
            cnpj: $cnpj,
        );

        $this->assertNull($user->cpf());
        $this->assertNotNull($user->cnpj());
        $this->assertSame('11222333000181', $user->cnpj()->toString());
        $this->assertSame('11.222.333/0001-81', $user->document());
    }

    public function test_create_user_with_both_documents(): void
    {
        $cpf = new Cpf('52998224725');
        $cnpj = new Cnpj('11222333000181');

        $user = new User(
            id: 'id-3',
            fullName: 'Both Docs',
            email: new Email('both@test.com'),
            password: new Password('password123'),
            type: UserType::NORMAL,
            cpf: $cpf,
            cnpj: $cnpj,
        );

        $this->assertNotNull($user->cpf());
        $this->assertNotNull($user->cnpj());
        $this->assertSame('529.982.247-25', $user->document());
    }

    public function test_user_with_timestamps(): void
    {
        $createdAt = new DateTimeImmutable('2025-01-01 12:00:00');
        $updatedAt = new DateTimeImmutable('2025-06-01 18:00:00');

        $user = new User(
            id: 'id-4',
            fullName: 'Stamped',
            email: new Email('stamped@test.com'),
            password: new Password('password123'),
            type: UserType::NORMAL,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertSame('2025-01-01 12:00:00', $user->createdAt()->format('Y-m-d H:i:s'));
        $this->assertSame('2025-06-01 18:00:00', $user->updatedAt()->format('Y-m-d H:i:s'));
    }

    public static function userTypeProvider(): Generator
    {
        yield 'normal user' => [UserType::NORMAL, true, false];
        yield 'shopkeeper user' => [UserType::SHOPKEEPER, false, true];
    }

    #[DataProvider('userTypeProvider')]
    public function test_user_type_methods(UserType $type, bool $expectedNormal, bool $expectedShopkeeper): void
    {
        $user = new User(
            id: 'id-5',
            fullName: 'Type Test',
            email: new Email('type@test.com'),
            password: new Password('password123'),
            type: $type,
        );

        $this->assertSame($expectedNormal, $user->isNormal());
        $this->assertSame($expectedShopkeeper, $user->isShopkeeper());
    }
}
