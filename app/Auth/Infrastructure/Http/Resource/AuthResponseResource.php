<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Resource;

use App\Common\Infrastructure\Http\Resource\AbstractResource;

final class AuthResponseResource extends AbstractResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
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
