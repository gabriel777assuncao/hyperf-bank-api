<?php

declare(strict_types=1);

namespace App\Auth\Domain\Contract;

use App\User\Domain\Entity\User;

interface AuthContract
{
    public function generateToken(User $user): string;

    public function resolveUserId(string $token): string;
}
