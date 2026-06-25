<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Amqp\Consumer;

use App\Transaction\Domain\Contract\NotifierContract;
use App\User\Domain\Contract\UserRepositoryContract;
use Hyperf\Amqp\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Amqp\Message\{ConsumerMessage, Type};
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use Throwable;

#[ConsumerAnnotation(exchange: 'transfer', routingKey: 'transfer.completed', queue: 'transfer_notification', nums: 1)]
final class TransferNotificationConsumer extends ConsumerMessage
{
    protected Type|string $type = Type::DIRECT;

    public function __construct(
        private readonly NotifierContract $notifier,
        private readonly UserRepositoryContract $userRepository,
        private readonly StdoutLoggerInterface $logger,
    ) {
    }

    /**
     * @param array{transaction_id: string, payee_id: string} $data
     */
    public function consume($data): Result
    {
        $user = $this->userRepository->findById($data['payee_id']);

        if ($user === null) {
            $this->logger->warning(sprintf('TransferNotificationConsumer: payee "%s" not found, dropping message.', $data['payee_id']));

            return Result::DROP;
        }

        try {
            $this->notifier->notify($user);

            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('TransferNotificationConsumer: notify failed for payee "%s": %s', $data['payee_id'], $e->getMessage()));

            return Result::NACK;
        }
    }
}
