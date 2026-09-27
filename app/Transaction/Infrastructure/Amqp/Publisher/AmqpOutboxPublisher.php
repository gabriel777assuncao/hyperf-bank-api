<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Amqp\Publisher;

use App\Transaction\Domain\Contract\OutboxPublisherContract;
use App\Transaction\Domain\Entity\OutboxEvent;
use App\Transaction\Infrastructure\Amqp\Producer\OutboxEventProducer;
use Hyperf\Amqp\Producer;

final readonly class AmqpOutboxPublisher implements OutboxPublisherContract
{
    public function __construct(
        private Producer $producer,
    ) {
    }

    public function publish(OutboxEvent $event): void
    {
        $this->producer->produce(new OutboxEventProducer($event));
    }
}
