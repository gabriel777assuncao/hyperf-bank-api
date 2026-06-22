<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\ValueObject\{Cnpj, Cpf, Email, Password};
use App\Wallet\Domain\Contract\WalletRepositoryContract;
use Hyperf\Database\Exception\QueryException;
use Ramsey\Uuid\Uuid;

class CreateUserService
{
    private const DUPLICATE_ENTRY_CODE = 23000;

    public function __construct(
            private readonly UserRepositoryContract $userRepository,
            private readonly WalletRepositoryContract $walletRepository,
            private readonly DatabaseManagerContract $databaseManager,
    ) {
    }

    public function execute(array $data): User
    {
        $email = new Email($data['email']);
        $password = new Password($data['password']);
        $type = UserType::from($data['type']);

        $cpf = isset($data['cpf']) && $data['cpf'] !== ''
                ? new Cpf($data['cpf'])
                : null;

        $cnpj = isset($data['cnpj']) && $data['cnpj'] !== ''
                ? new Cnpj($data['cnpj'])
                : null;

        $user = new User(
                id: (string) Uuid::uuid4(),
                fullName: $data['full_name'],
                email: $email,
                password: $password,
                type: $type,
                cpf: $cpf,
                cnpj: $cnpj,
        );

        $this->assertUnique($user);

        try {
            $this->databaseManager->transaction(function () use ($user): void {
                $this->userRepository->save($user);
                $this->walletRepository->create($user->id());
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateEntry($exception)) {
                throw new UserAlreadyExistsException();
            }

            throw $exception;
        }

        return $user;
    }

    private function assertUnique(User $user): void
    {
        $cpf = $user->cpf();

        if ($cpf !== null && $this->userRepository->findByCpf($cpf->toString()) !== null) {
            throw new UserAlreadyExistsException(sprintf('A user with document "%s" already exists.', $cpf->formatted()));
        }

        $cnpj = $user->cnpj();

        if ($cnpj !== null && $this->userRepository->findByCnpj($cnpj->toString()) !== null) {
            throw new UserAlreadyExistsException(sprintf('A user with document "%s" already exists.', $cnpj->formatted()));
        }

        if ($this->userRepository->findByEmail($user->email()->toString()) !== null) {
            throw new UserAlreadyExistsException(sprintf('A user with email "%s" already exists.', $user->email()->toString()));
        }
    }

    private function isDuplicateEntry(QueryException $exception): bool
    {
        return (int) $exception->getCode() === self::DUPLICATE_ENTRY_CODE;
    }
}