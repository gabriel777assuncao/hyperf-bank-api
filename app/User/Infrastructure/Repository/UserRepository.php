<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\ValueObject\{Cnpj, Cpf, Email, Password};
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Infrastructure\Model\UserModel;
use DateTimeImmutable;
use DateTimeInterface;
use Hyperf\Database\Exception\QueryException;

final class UserRepository implements UserRepositoryContract
{
    /**
     * Código de erro específico do MySQL para violação de UNIQUE (ER_DUP_ENTRY).
     * Usamos o código do driver (errorInfo[1]), e não o SQLSTATE 23000, pois
     * 23000 cobre toda a classe de integrity violation (FK, NOT NULL, etc).
     */
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    public function findById(string $id): ?User
    {
        $user = UserModel::query()->find($id);

        if ($user === null) {
            return null;
        }

        return $this->toEntity($user);
    }

    public function findByDocument(string $document): ?User
    {
        $user = UserModel::query()
            ->where('cpf', $document)
            ->orWhere('cnpj', $document)
            ->first();

        if ($user === null) {
            return null;
        }

        return $this->toEntity($user);
    }

    public function findByCpf(string $cpf): ?User
    {
        $user = UserModel::query()->where('cpf', $cpf)->first();

        if ($user === null) {
            return null;
        }

        return $this->toEntity($user);
    }

    public function findByCnpj(string $cnpj): ?User
    {
        $user = UserModel::query()->where('cnpj', $cnpj)->first();

        if ($user === null) {
            return null;
        }

        return $this->toEntity($user);
    }

    public function findByEmail(string $email): ?User
    {
        $user = UserModel::query()->where('email', $email)->first();

        if ($user === null) {
            return null;
        }

        return $this->toEntity($user);
    }

    public function save(User $user): void
    {
        try {
            UserModel::query()->updateOrCreate(
                ['id' => $user->id()],
                [
                    'full_name' => $user->fullName(),
                    'cpf' => $user->cpf()?->toString(),
                    'cnpj' => $user->cnpj()?->toString(),
                    'email' => $user->email()->toString(),
                    'password' => $user->password()->toString(),
                    'type' => $user->type()->value,
                ],
            );
        } catch (QueryException $exception) {
            if ($this->isDuplicateEntry($exception)) {
                throw new UserAlreadyExistsException();
            }

            throw $exception;
        }
    }

    private function isDuplicateEntry(QueryException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === self::MYSQL_DUPLICATE_ENTRY;
    }

    private function toEntity(UserModel $user): User
    {
        return new User(
            id: $user->id,
            fullName: $user->full_name,
            email: new Email($user->email),
            password: Password::fromHash($user->password),
            type: UserType::from($user->type),
            cpf: $user->cpf !== null ? new Cpf($user->cpf) : null,
            cnpj: $user->cnpj !== null ? new Cnpj($user->cnpj) : null,
            createdAt: $user->created_at instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($user->created_at)
                : null,
            updatedAt: $user->updated_at instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($user->updated_at)
                : null,
        );
    }
}
