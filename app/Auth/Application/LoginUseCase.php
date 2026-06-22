<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\Contract\AuthContract;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\{InvalidCredentialsException, UserNotFoundException};
use App\User\Domain\Contract\UserRepositoryContract;

final class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryContract $userRepository,
        private readonly AuthContract           $auth,
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
            throw new UserNotFoundException(sprintf('User with document "%s" not found.', $document));
        }

        if (! $user->password()->verify($data['password'])) {
            throw new InvalidCredentialsException();
        }

        $token = $this->auth->generateToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
