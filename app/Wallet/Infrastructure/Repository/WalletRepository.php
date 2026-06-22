<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Repository;

use App\Wallet\Domain\Contract\WalletRepositoryContract;
use App\Wallet\Domain\Entity\Wallet;
use App\Wallet\Domain\Exception\WalletNotFoundException;
use App\Wallet\Domain\ValueObject\Money;
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

    public function findByUserId(string $userId): ?Wallet
    {
        $wallet = WalletModel::query()->where('user_id', $userId)->first();

        if ($wallet === null) {
            return null;
        }

        return $this->toEntity($wallet);
    }

    public function findByUserIdForUpdate(string $userId): Wallet
    {
        $wallet = WalletModel::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if ($wallet === null) {
            throw new WalletNotFoundException(sprintf('Wallet not found for user "%s".', $userId));
        }

        return $this->toEntity($wallet);
    }

    public function save(Wallet $wallet): void
    {
        WalletModel::query()
            ->where('id', $wallet->id())
            ->update(['balance' => $wallet->balance()->toCents()]);
    }

    private function toEntity(WalletModel $wallet): Wallet
    {
        return new Wallet(
            id: $wallet->id,
            userId: $wallet->user_id,
            balance: new Money((int) $wallet->balance),
        );
    }
}
