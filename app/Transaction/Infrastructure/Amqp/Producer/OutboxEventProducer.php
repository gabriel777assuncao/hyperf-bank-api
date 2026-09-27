<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Amqp\Producer;

use App\Transaction\Domain\Entity\OutboxEvent;
use Hyperf\Amqp\Annotation\Producer as ProducerAnnotation;
use Hyperf\Amqp\Message\{ProducerMessage, Type};

#[ProducerAnnotation(exchange: 'transfer', routingKey: 'transfer.completed')]
final class OutboxEventProducer extends ProducerMessage
{
    protected Type|string $type = Type::DIRECT;

    public function __construct(OutboxEvent $event)
    {
        $this->payload = $event->payload();
    }
}
