<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Contract;

use App\Wallet\Domain\Entity\Wallet;

interface WalletRepositoryContract
{
    public function create(string $userId): void;

    public function findByUserIdForUpdate(string $userId): Wallet;

    public function save(Wallet $wallet): void;
}
