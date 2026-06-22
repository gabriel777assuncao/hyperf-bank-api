<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\Contract\AuthContract;
use App\User\Application\CreateUserService;
use App\User\Domain\Entity\User;

final readonly class RegisterUseCase
{
    public function __construct(
        private CreateUserService $createUserService,
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
