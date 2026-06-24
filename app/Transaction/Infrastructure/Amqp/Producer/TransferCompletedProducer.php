<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer as ProducerAnnotation;
use Hyperf\Amqp\Message\{ProducerMessage, Type};

#[ProducerAnnotation(exchange: 'transfer', routingKey: 'transfer.completed')]
final class TransferCompletedProducer extends ProducerMessage
{
    protected Type|string $type = Type::DIRECT;

    public function __construct(string $transactionId, string $payeeId)
    {
        $this->payload = [
            'transaction_id' => $transactionId,
            'payee_id' => $payeeId,
        ];
    }
}
