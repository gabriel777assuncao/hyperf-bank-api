<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

use App\Transaction\Domain\Entity\Transaction;

interface TransactionRepositoryContract
{
    public function save(Transaction $transaction): void;
}
