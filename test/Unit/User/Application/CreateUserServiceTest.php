<?php

declare(strict_types=1);

namespace HyperfTest\Unit\User\Application;

use App\User\Application\CreateUserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\ValueObject\{Email, Password};
use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\User\Infrastructure\Contract\UserRepositoryContract;
use App\Wallet\Infrastructure\Contract\WalletRepositoryContract;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \App\User\Application\CreateUserService
 */
final class CreateUserServiceTest extends TestCase
{
    private UserRepositoryContract&MockObject $userRepository;
    private WalletRepositoryContract&MockObject $walletRepository;
    private DatabaseManagerContract&MockObject $databaseManager;
    private CreateUserService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryContract::class);
        $this->walletRepository = $this->createMock(WalletRepositoryContract::class);
        $this->databaseManager = $this->createMock(DatabaseManagerContract::class);

        $this->databaseManager
            ->method('transaction')
            ->willReturnCallback(fn (callable $callback) => $callback());

        $this->service = new CreateUserService(
            $this->userRepository,
            $this->walletRepository,
            $this->databaseManager,
        );
    }

    public function test_create_user_with_cpf(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'João Silva',
            'cpf' => '52998224725',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('52998224725', $user->cpf()->toString());
        $this->assertNull($user->cnpj());
        $this->assertSame(UserType::NORMAL, $user->type());
        $this->assertSame('joao@example.com', $user->email()->toString());
    }

    public function test_create_user_with_cnpj(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'Loja Exemplo',
            'cnpj' => '11222333000181',
            'email' => 'loja@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->cpf());
        $this->assertSame('11222333000181', $user->cnpj()->toString());
        $this->assertSame(UserType::SHOPKEEPER, $user->type());
        $this->assertSame('loja@example.com', $user->email()->toString());
    }

    public function test_create_user_with_both_documents(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'Pessoa com Ambos',
            'cpf' => '52998224725',
            'cnpj' => '11222333000181',
            'email' => 'ambos@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame('52998224725', $user->cpf()->toString());
        $this->assertSame('11222333000181', $user->cnpj()->toString());
    }

    public function test_create_user_normal_without_cpf_or_cnpj(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'Sem Documento',
            'email' => 'semdoc@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertNull($user->cpf());
        $this->assertNull($user->cnpj());
    }

    public function test_throws_when_cpf_already_exists(): void
    {
        $existingUser = new User(
            id: 'existing-id',
            fullName: 'Existing',
            email: new Email('existing@example.com'),
            password: Password::fromHash('$2y$10$hashedpasswordvaluehere1234567890abc'),
            type: UserType::NORMAL,
        );
        $this->userRepository->method('findByCpf')->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('A user with document "529.982.247-25" already exists.');

        $this->service->execute([
            'full_name' => 'Duplicado',
            'cpf' => '52998224725',
            'email' => 'dup@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);
    }

    public function test_throws_when_cnpj_already_exists(): void
    {
        $existingUser = new User(
            id: 'existing-cnpj-id',
            fullName: 'Existing CNPJ',
            email: new Email('cnpjexisting@example.com'),
            password: Password::fromHash('$2y$10$hashedpasswordvaluehere1234567890abc'),
            type: UserType::SHOPKEEPER,
        );
        $this->userRepository->method('findByCnpj')->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('A user with document "11.222.333/0001-81" already exists.');

        $this->service->execute([
            'full_name' => 'CNPJ Duplicado',
            'cnpj' => '11222333000181',
            'email' => 'cnpjdup@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ]);
    }

    public function test_throws_when_email_already_exists(): void
    {
        $existingUser = new User(
            id: 'existing-email-id',
            fullName: 'Existing Email',
            email: new Email('existing@example.com'),
            password: Password::fromHash('$2y$10$hashedpasswordvaluehere1234567890abc'),
            type: UserType::NORMAL,
        );
        $this->userRepository->method('findByEmail')->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage('A user with email "dup@example.com" already exists.');

        $this->service->execute([
            'full_name' => 'Email Duplicado',
            'cpf' => '52998224725',
            'email' => 'dup@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);
    }

    public function test_wallet_created_with_user_id(): void
    {
        $actualUserId = null;
        $this->walletRepository
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (string $userId) use (&$actualUserId): void {
                $actualUserId = $userId;
            });

        $user = $this->service->execute([
            'full_name' => 'Com Wallet',
            'cpf' => '52998224725',
            'email' => 'wallet@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame($user->id(), $actualUserId);
    }

    public function test_user_saved_before_wallet(): void
    {
        $steps = [];

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function () use (&$steps): void {
                $steps[] = 'user_saved';
            });

        $this->walletRepository
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () use (&$steps): void {
                $steps[] = 'wallet_created';
            });

        $this->service->execute([
            'full_name' => 'Ordem',
            'cpf' => '52998224725',
            'email' => 'ordem@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame(['user_saved', 'wallet_created'], $steps);
    }

    public static function userTypeProvider(): Generator
    {
        yield 'normal' => ['NORMAL', UserType::NORMAL];
        yield 'shopkeeper' => ['SHOPKEEPER', UserType::SHOPKEEPER];
    }

    #[DataProvider('userTypeProvider')]
    public function test_create_user_with_type(string $input, UserType $expected): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'Type Test',
            'cpf' => '52998224725',
            'email' => 'type@example.com',
            'password' => 'password123',
            'type' => $input,
        ]);

        $this->assertSame($expected, $user->type());
    }

    public function test_create_user_with_formatted_cpf(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'CPF Formatado',
            'cpf' => '529.982.247-25',
            'email' => 'formatado@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame('52998224725', $user->cpf()->toString());
        $this->assertSame('529.982.247-25', $user->cpf()->formatted());
    }

    public function test_create_user_with_formatted_cnpj(): void
    {
        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $user = $this->service->execute([
            'full_name' => 'CNPJ Formatado',
            'cnpj' => '11.222.333/0001-81',
            'email' => 'cnpjformatado@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ]);

        $this->assertSame('11222333000181', $user->cnpj()->toString());
        $this->assertSame('11.222.333/0001-81', $user->cnpj()->formatted());
    }

    public function test_does_not_query_cpf_when_cpf_not_provided(): void
    {
        $this->userRepository
            ->expects($this->never())
            ->method('findByCpf');

        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $this->service->execute([
            'full_name' => 'Sem CPF',
            'cnpj' => '11222333000181',
            'email' => 'semcpf@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ]);
    }

    public function test_does_not_query_cnpj_when_cnpj_not_provided(): void
    {
        $this->userRepository
            ->expects($this->never())
            ->method('findByCnpj');

        $this->userRepository->expects($this->once())->method('save');
        $this->walletRepository->expects($this->once())->method('create');

        $this->service->execute([
            'full_name' => 'Sem CNPJ',
            'cpf' => '52998224725',
            'email' => 'semcnpj@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);
    }

    public function test_transaction_wraps_save_and_wallet_creation(): void
    {
        $steps = [];

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function () use (&$steps): void {
                $steps[] = 'save';
            });

        $this->walletRepository
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () use (&$steps): void {
                $steps[] = 'create';
            });

        $this->service->execute([
            'full_name' => 'Transactional',
            'cpf' => '52998224725',
            'email' => 'transactional@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame(['save', 'create'], $steps);
    }
}
