<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Common\Domain\AuthContract;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\{InvalidCredentialsException, UserNotFoundException};
use App\User\Infrastructure\Contract\UserRepositoryContract;

final class LoginUseCase
{
    public function __construct(
        private UserRepositoryContract $userRepository,
        private AuthContract $auth,
    ) {
    }

    /**
     * @param array<string, string> $data
     * @return array{token: string, user: User}
     */
    public function execute(array $data): array
    {
        $document = preg_replace('/\D/', '', $data['document']);

        $user = $this->userRepository->findByDocument($document);

        if ($user === null) {
            throw UserNotFoundException::byDocument($document);
        }

        if (! $user->password()->verify($data['password'])) {
            throw new InvalidCredentialsException();
        }

        $token = $this->auth->encode([
            'sub' => $user->id(),
            'type' => $user->type()->value,
        ]);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
