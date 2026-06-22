<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Auth\Application;

use App\Auth\Application\RegisterUseCase;
use App\Auth\Domain\Contract\AuthContract;
use App\User\Application\CreateUserService;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\ValueObject\{Email, Password};
use Generator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 * @covers \App\Auth\Application\RegisterUseCase
 */
final class RegisterUseCaseTest extends TestCase
{
    private CreateUserService $createUserService;
    private AuthContract $auth;
    private RegisterUseCase $useCase;

    protected function setUp(): void
    {
        $this->createUserService = Mockery::mock(CreateUserService::class);
        $this->auth = Mockery::mock(AuthContract::class);
        $this->useCase = new RegisterUseCase($this->createUserService, $this->auth);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_token_and_user(): void
    {
        $user = new User(
            id: 'abc-123',
            fullName: 'João',
            email: new Email('joao@example.com'),
            password: new Password('password123'),
            type: UserType::NORMAL,
        );

        $this->createUserService
            ->shouldReceive('execute')
            ->once()
            ->andReturn($user);

        $this->auth
            ->shouldReceive('generateToken')
            ->once()
            ->with($user)
            ->andReturn('jwt.token.here');

        $result = $this->useCase->execute([
            'full_name' => 'João',
            'cpf' => '52998224725',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame('jwt.token.here', $result['token']);
        $this->assertSame($user, $result['user']);
    }

    public function test_passes_input_to_create_user_service(): void
    {
        $input = [
            'full_name' => 'Maria',
            'cnpj' => '11222333000181',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ];

        $user = new User(
            id: 'def-456',
            fullName: 'Maria',
            email: new Email('maria@example.com'),
            password: new Password('password123'),
            type: UserType::SHOPKEEPER,
        );

        $this->createUserService
            ->shouldReceive('execute')
            ->once()
            ->with($input)
            ->andReturn($user);

        $this->auth->shouldReceive('generateToken')->andReturn('token');

        $result = $this->useCase->execute($input);

        $this->assertSame($user, $result['user']);
    }

    public function test_generate_token_called_with_created_user(): void
    {
        $user = new User(
            id: 'user-uuid-1234',
            fullName: 'Test',
            email: new Email('test@example.com'),
            password: new Password('password123'),
            type: UserType::SHOPKEEPER,
        );

        $this->createUserService
            ->shouldReceive('execute')
            ->andReturn($user);

        $this->auth
            ->shouldReceive('generateToken')
            ->once()
            ->with($user)
            ->andReturn('token');

        $result = $this->useCase->execute([
            'full_name' => 'Test',
            'cnpj' => '11222333000181',
            'email' => 'test@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
        ]);

        $this->assertSame('token', $result['token']);
    }

    public function test_does_not_generate_token_before_creating_user(): void
    {
        $user = new User(
            id: 'id',
            fullName: 'Late',
            email: new Email('late@example.com'),
            password: new Password('password123'),
            type: UserType::NORMAL,
        );

        $order = [];
        $this->createUserService
            ->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function () use ($user, &$order): User {
                $order[] = 'create_user';

                return $user;
            });

        $this->auth
            ->shouldReceive('generateToken')
            ->once()
            ->andReturnUsing(function () use (&$order): string {
                $order[] = 'generate_token';

                return 'token';
            });

        $this->useCase->execute([
            'full_name' => 'Late',
            'cpf' => '52998224725',
            'email' => 'late@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $this->assertSame(['create_user', 'generate_token'], $order);
    }

    public static function userTypeProvider(): Generator
    {
        yield 'normal' => ['NORMAL'];
        yield 'shopkeeper' => ['SHOPKEEPER'];
    }

    #[DataProvider('userTypeProvider')]
    public function test_generate_token_called_for_user_type(string $type): void
    {
        $user = new User(
            id: 'id',
            fullName: 'Type',
            email: new Email('type@example.com'),
            password: new Password('password123'),
            type: UserType::from($type),
        );

        $this->createUserService
            ->shouldReceive('execute')
            ->andReturn($user);

        $this->auth
            ->shouldReceive('generateToken')
            ->once()
            ->with($user)
            ->andReturn('token');

        $result = $this->useCase->execute([
            'full_name' => 'Type',
            'cpf' => '52998224725',
            'email' => 'type@example.com',
            'password' => 'password123',
            'type' => $type,
        ]);

        $this->assertSame('token', $result['token']);
    }

    public function test_register_use_case_has_clean_signature(): void
    {
        $reflection = new ReflectionClass(RegisterUseCase::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);

        $names = array_map(fn ($p) => $p->getName(), $params);
        $this->assertContains('createUserService', $names);
        $this->assertContains('auth', $names);

        $this->assertLessThanOrEqual(40, count(file($reflection->getFileName())));
    }
}
