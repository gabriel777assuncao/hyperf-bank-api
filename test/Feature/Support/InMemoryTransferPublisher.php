<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Support;

use App\Transaction\Domain\Contract\TransferPublisherContract;

final class InMemoryTransferPublisher implements TransferPublisherContract
{
    /** @var list<array{transactionId: string, payeeId: string}> */
    public array $published = [];

    public function publishTransferCompleted(string $transactionId, string $payeeId): void
    {
        $this->published[] = ['transactionId' => $transactionId, 'payeeId' => $payeeId];
    }

    public function reset(): void
    {
        $this->published = [];
    }
}
