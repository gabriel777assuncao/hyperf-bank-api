<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Repository;

use App\Transaction\Domain\Contract\TransactionRepositoryContract;
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Infrastructure\Model\TransactionModel;

final class TransactionRepository implements TransactionRepositoryContract
{
    public function save(Transaction $transaction): void
    {
        TransactionModel::query()->updateOrCreate(
            ['id' => $transaction->id()],
            [
                'payer_id' => $transaction->payerId(),
                'payee_id' => $transaction->payeeId(),
                'value' => $transaction->amount()->toCents(),
                'status' => $transaction->status()->value,
            ],
        );
    }
}
