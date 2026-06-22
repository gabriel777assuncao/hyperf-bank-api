<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Repository;

use App\Wallet\Infrastructure\Contract\WalletRepositoryContract;
use App\Wallet\Infrastructure\Model\WalletModel;
use Ramsey\Uuid\Uuid;

final class WalletRepository implements WalletRepositoryContract
{
    public function create(string $userId): void
    {
        WalletModel::query()->create([
            'id' => (string) Uuid::uuid4(),
            'user_id' => $userId,
            'balance' => 0,
        ]);
    }
}
