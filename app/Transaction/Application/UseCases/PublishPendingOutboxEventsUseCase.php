<?php

declare(strict_types=1);

namespace App\Transaction\Application\UseCases;

use App\Transaction\Domain\Contract\{OutboxEventRepositoryContract, OutboxPublisherContract};
use App\Transaction\Domain\Entity\OutboxEvent;
use App\Transaction\Domain\Enum\OutboxEventStatus;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PublishPendingOutboxEventsUseCase
{
    public function __construct(
        private OutboxEventRepositoryContract $outboxEventRepository,
        private OutboxPublisherContract $outboxPublisher,
        private LoggerInterface $logger,
        private int $maxTries = 5,
        private int $maxDelaySeconds = 60,
        private int $batchSize = 100,
    ) {
    }

    public function execute(): void
    {
        $now = new DateTimeImmutable();

        $notReady = $this->outboxEventRepository->countPendingNotReady();

        if ($notReady > 0) {
            $this->logger->warning(sprintf(
                '%d pending outbox event(s) have no completed transaction and were skipped.',
                $notReady,
            ));
        }

        foreach ($this->outboxEventRepository->findReadyToPublish($this->batchSize, $now) as $event) {
            try {
                $this->outboxPublisher->publish($event);
            } catch (Throwable $exception) {
                $this->handlePublishFailure($event, $now, $exception);

                continue;
            }

            $event->markAsPublished($now);
            $this->outboxEventRepository->save($event);
        }
    }

    private function handlePublishFailure(OutboxEvent $event, DateTimeImmutable $now, Throwable $exception): void
    {
        $event->registerFailure($now, $this->maxTries, $this->maxDelaySeconds);

        if ($event->status() === OutboxEventStatus::FAILED) {
            $this->logger->error(sprintf(
                'Outbox event "%s" failed permanently after %d attempt(s): %s',
                $event->id(),
                $event->tries(),
                $exception->getMessage(),
            ));
        } else {
            $this->logger->warning(sprintf(
                'Failed to publish outbox event "%s" (attempt %d), retrying at %s: %s',
                $event->id(),
                $event->tries(),
                $event->nextRetryAt()?->format(DATE_ATOM) ?? 'unknown',
                $exception->getMessage(),
            ));
        }

        try {
            $this->outboxEventRepository->save($event);
        } catch (Throwable $saveException) {
            $this->logger->error(sprintf(
                'Failed to record retry state for outbox event "%s": %s',
                $event->id(),
                $saveException->getMessage(),
            ));
        }
    }
}
