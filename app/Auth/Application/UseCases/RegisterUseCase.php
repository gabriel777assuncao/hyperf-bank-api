<?php

declare(strict_types=1);

namespace App\Auth\Application\UseCases;

use App\Auth\Domain\Contract\AuthContract;
use App\User\Application\UseCases\CreateUserUseCase;
use App\User\Domain\Entity\User;

final readonly class RegisterUseCase
{
    public function __construct(
        private CreateUserUseCase $createUserService,
        private AuthContract $auth,
    ) {
    }

    /**
     * @return array{token: string, user: User}
     */
    public function execute(array $data): array
    {
        $user = $this->createUserService->execute($data);

        $token = $this->auth->generateToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
