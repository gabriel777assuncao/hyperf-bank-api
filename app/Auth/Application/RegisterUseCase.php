<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\AuthContract;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\ValueObject\{Cnpj, Cpf, Email, Password};
use App\User\Infrastructure\Contract\UserRepositoryContract;
use Ramsey\Uuid\Uuid;

final readonly class RegisterUseCase
{
    public function __construct(
        private UserRepositoryContract $userRepository,
        private AuthContract $auth,
    ) {
    }

    /**
     * @return array{token: string, user: User}
     */
    public function execute(array $data): array
    {
        $type = UserType::from($data['type']);
        $email = new Email($data['email']);
        $password = new Password($data['password']);

        if (isset($data['cpf']) && $data['cpf'] !== '') {
            $cpf = new Cpf($data['cpf']);
        }

        if (isset($data['cnpj']) && $data['cnpj'] !== '') {
            $cnpj = new Cnpj($data['cnpj']);
        }

        $user = new User(
            id: (string) Uuid::uuid4(),
            fullName: $data['full_name'],
            email: $email,
            password: $password,
            type: $type,
            cpf: $cpf ?? null,
            cnpj: $cnpj ?? null,
        );

        $this->assertUnique($user, $email);
        $this->userRepository->save($user);

        $token = $this->auth->encode([
            'sub' => $user->id(),
            'type' => $user->type()->value,
        ]);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    private function assertUnique(User $user, Email $email): void
    {
        $cpf = $user->cpf();
        $cnpj = $user->cnpj();

        if (! is_null($cpf)) {
            $existingCpf = $this->userRepository->findByCpf($cpf->toString());

            if (! is_null($existingCpf)) {
                throw UserAlreadyExistsException::document($cpf->formatted());
            }
        }

        if (! is_null($cnpj)) {
            $existingCnpj = $this->userRepository->findByCnpj($cnpj->toString());

            if (! is_null($existingCnpj)) {
                throw UserAlreadyExistsException::document($cnpj->formatted());
            }
        }

        $byEmail = $this->userRepository->findByEmail($email->toString());

        if (! is_null($byEmail)) {
            throw UserAlreadyExistsException::email($email->toString());
        }
    }
}
