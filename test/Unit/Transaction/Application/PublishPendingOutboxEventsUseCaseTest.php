<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Application;

use App\Transaction\Application\UseCases\PublishPendingOutboxEventsUseCase;
use App\Transaction\Domain\Contract\{OutboxEventRepositoryContract, OutboxPublisherContract};
use App\Transaction\Domain\Entity\{OutboxEvent, Transaction};
use App\Transaction\Domain\Enum\{OutboxEventStatus, TransactionStatus};
use App\Wallet\Domain\ValueObject\Money;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @internal
 * @covers \App\Transaction\Application\UseCases\PublishPendingOutboxEventsUseCase
 */
final class PublishPendingOutboxEventsUseCaseTest extends TestCase
{
    private OutboxEventRepositoryContract&MockObject $outboxEventRepository;

    private OutboxPublisherContract&MockObject $outboxPublisher;

    private LoggerInterface&MockObject $logger;

    private PublishPendingOutboxEventsUseCase $useCase;

    protected function setUp(): void
    {
        $this->outboxEventRepository = $this->createMock(OutboxEventRepositoryContract::class);
        $this->outboxPublisher = $this->createMock(OutboxPublisherContract::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new PublishPendingOutboxEventsUseCase(
            $this->outboxEventRepository,
            $this->outboxPublisher,
            $this->logger,
            5,
            60,
            100,
        );
    }

    public function test_publishes_ready_events_and_marks_them_as_published(): void
    {
        $first = $this->makeEvent('event-1');
        $second = $this->makeEvent('event-2');

        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(0);
        $this->outboxEventRepository
            ->expects($this->once())
            ->method('findReadyToPublish')
            ->with(100, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn([$first, $second]);

        $this->outboxPublisher->expects($this->exactly(2))->method('publish');

        $this->outboxEventRepository
            ->expects($this->exactly(2))
            ->method('save')
            ->with($this->callback(
                fn (OutboxEvent $event): bool => $event->status() === OutboxEventStatus::PUBLISHED
                    && $event->publishedAt() !== null,
            ));

        $this->useCase->execute();

        $this->assertSame(OutboxEventStatus::PUBLISHED, $first->status());
        $this->assertSame(OutboxEventStatus::PUBLISHED, $second->status());
    }

    public function test_does_nothing_when_there_are_no_ready_events(): void
    {
        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(0);
        $this->outboxEventRepository->method('findReadyToPublish')->willReturn([]);

        $this->outboxPublisher->expects($this->never())->method('publish');
        $this->outboxEventRepository->expects($this->never())->method('save');

        $this->useCase->execute();
    }

    public function test_logs_warning_when_there_are_not_ready_events(): void
    {
        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(3);
        $this->outboxEventRepository->method('findReadyToPublish')->willReturn([]);

        $this->logger->expects($this->once())->method('warning');

        $this->useCase->execute();
    }

    public function test_schedules_retry_when_publish_throws(): void
    {
        $event = $this->makeEvent('event-1');

        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(0);
        $this->outboxEventRepository->method('findReadyToPublish')->willReturn([$event]);

        $this->outboxPublisher
            ->method('publish')
            ->willThrowException(new RuntimeException('AMQP down'));

        $this->outboxEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (OutboxEvent $saved): bool => $saved->status() === OutboxEventStatus::PENDING
                    && $saved->tries() === 1
                    && $saved->nextRetryAt() !== null,
            ));

        $this->logger->expects($this->once())->method('warning');

        $this->useCase->execute();

        $this->assertSame(OutboxEventStatus::PENDING, $event->status());
        $this->assertSame(1, $event->tries());
    }

    public function test_marks_event_as_failed_when_max_tries_reached(): void
    {
        $event = $this->makeEventWithTries('event-1', 4);

        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(0);
        $this->outboxEventRepository->method('findReadyToPublish')->willReturn([$event]);

        $this->outboxPublisher
            ->method('publish')
            ->willThrowException(new RuntimeException('AMQP down'));

        $this->outboxEventRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (OutboxEvent $saved): bool => $saved->status() === OutboxEventStatus::FAILED
                    && $saved->tries() === 5,
            ));

        $this->logger->expects($this->once())->method('error');

        $this->useCase->execute();

        $this->assertSame(OutboxEventStatus::FAILED, $event->status());
        $this->assertNull($event->nextRetryAt());
    }

    public function test_continues_with_remaining_events_after_a_publish_failure(): void
    {
        $first = $this->makeEvent('event-1');
        $second = $this->makeEvent('event-2');

        $this->outboxEventRepository->method('countPendingNotReady')->willReturn(0);
        $this->outboxEventRepository->method('findReadyToPublish')->willReturn([$first, $second]);

        $this->outboxPublisher
            ->method('publish')
            ->willReturnCallback(function (OutboxEvent $event): void {
                if ($event->id() === 'event-1') {
                    throw new RuntimeException('AMQP down');
                }
            });

        $savedStatuses = [];
        $this->outboxEventRepository
            ->method('save')
            ->willReturnCallback(function (OutboxEvent $event) use (&$savedStatuses): void {
                $savedStatuses[$event->id()] = $event->status();
            });

        $this->useCase->execute();

        $this->assertSame(OutboxEventStatus::PENDING, $savedStatuses['event-1']);
        $this->assertSame(OutboxEventStatus::PUBLISHED, $savedStatuses['event-2']);
    }

    private function makeEvent(string $id): OutboxEvent
    {
        return OutboxEvent::forCompletedTransfer($id, $this->makeTransaction($id));
    }

    private function makeEventWithTries(string $id, int $tries): OutboxEvent
    {
        $event = $this->makeEvent($id);
        $now = new DateTimeImmutable('2026-01-01 00:00:00');

        for ($attempt = 0; $attempt < $tries; ++$attempt) {
            $event->registerFailure($now, PHP_INT_MAX, 60);
        }

        return $event;
    }

    private function makeTransaction(string $id): Transaction
    {
        return new Transaction(
            id: 'tx-'.$id,
            payerId: 'payer-1',
            payeeId: 'payee-1',
            amount: new Money(1000),
            status: TransactionStatus::COMPLETED,
        );
    }
}
