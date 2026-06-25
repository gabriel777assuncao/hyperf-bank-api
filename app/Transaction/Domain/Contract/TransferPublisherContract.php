<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

interface TransferPublisherContract
{
    public function publishTransferCompleted(string $transactionId, string $payeeId): void;
}
