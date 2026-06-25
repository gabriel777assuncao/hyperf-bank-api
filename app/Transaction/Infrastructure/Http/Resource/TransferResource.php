<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Http\Resource;

use App\Common\Infrastructure\Http\Resource\AbstractResource;
use App\Transaction\Domain\Entity\Transaction;

final class TransferResource extends AbstractResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var Transaction $transaction */
        $transaction = $this->resource;

        return [
            'id' => $transaction->id(),
            'payer_id' => $transaction->payerId(),
            'payee_id' => $transaction->payeeId(),
            'value' => round($transaction->amount()->toCents() / 100, 2),
            'status' => $transaction->status()->value,
            'created_at' => $transaction->createdAt()?->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }
}
