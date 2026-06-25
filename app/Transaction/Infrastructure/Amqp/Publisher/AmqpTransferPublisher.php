<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Amqp\Publisher;

use App\Transaction\Domain\Contract\TransferPublisherContract;
use App\Transaction\Infrastructure\Amqp\Producer\TransferCompletedProducer;
use Hyperf\Amqp\Producer;
use Hyperf\Contract\StdoutLoggerInterface;
use Throwable;

final readonly class AmqpTransferPublisher implements TransferPublisherContract
{
    public function __construct(
        private Producer $producer,
        private StdoutLoggerInterface $logger,
    ) {
    }

    public function publishTransferCompleted(string $transactionId, string $payeeId): void
    {
        try {
            $this->producer->produce(new TransferCompletedProducer($transactionId, $payeeId));
        } catch (Throwable $exception) {
            $this->logger->error(sprintf('Failed to publish TransferCompleted message: %s', $exception->getMessage()));
        }
    }
}
