<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Contract;

interface WalletRepositoryContract
{
    public function create(string $userId): void;
}
