<?php

declare(strict_types=1);

namespace App\Auth\Http\Resource;

use App\Common\Http\Resource\AbstractResource;
use App\User\Domain\Entity\User;

final class AuthResponseResource extends AbstractResource
{
    public function toArray(): array
    {
        /** @var array{token: string, user: User} $data */
        ['token' => $token, 'user' => $user] = $this->resource;

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id(),
                'full_name' => $user->fullName(),
                'email' => $user->email()->toString(),
                'type' => $user->type()->value,
                'cpf' => $user->cpf()?->formatted(),
                'cnpj' => $user->cnpj()?->formatted(),
            ],
        ];
    }
}
