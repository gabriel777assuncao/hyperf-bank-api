<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Repository;

use App\Transaction\Domain\Contract\OutboxEventRepositoryContract;
use App\Transaction\Domain\Entity\OutboxEvent;
use App\Transaction\Domain\Enum\{OutboxEventStatus, TransactionStatus};
use App\Transaction\Infrastructure\Model\OutboxEventModel;
use DateTimeImmutable;
use DateTimeInterface;

final class OutboxEventRepository implements OutboxEventRepositoryContract
{
    public function save(OutboxEvent $event): void
    {
        OutboxEventModel::query()->updateOrCreate(
            ['id' => $event->id()],
            [
                'aggregate_id' => $event->aggregateId(),
                'status' => $event->status()->value,
                'tries' => $event->tries(),
                'next_retry_at' => $event->nextRetryAt(),
                'payload' => $event->payload(),
                'published_at' => $event->publishedAt(),
            ],
        );
    }

    /**
     * @return array<OutboxEvent>
     */
    public function findReadyToPublish(int $limit, DateTimeImmutable $now): array
    {
        return OutboxEventModel::query()
            ->where('outbox_events.status', OutboxEventStatus::PENDING->value)
            ->where(fn ($query) => $query
                ->whereNull('outbox_events.next_retry_at')
                ->orWhere('outbox_events.next_retry_at', '<=', $now->format('Y-m-d H:i:s.u')))
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('transactions')
                ->whereColumn('transactions.id', 'outbox_events.aggregate_id')
                ->where('transactions.status', TransactionStatus::COMPLETED->value))
            ->orderBy('outbox_events.created_at')
            ->limit($limit)
            ->get()
            ->map(fn (OutboxEventModel $event): OutboxEvent => $this->toEntity($event))
            ->all();
    }

    public function countPendingNotReady(): int
    {
        return OutboxEventModel::query()
            ->where('outbox_events.status', OutboxEventStatus::PENDING->value)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('transactions')
                ->whereColumn('transactions.id', 'outbox_events.aggregate_id')
                ->where('transactions.status', TransactionStatus::COMPLETED->value))
            ->count();
    }

    private function toEntity(OutboxEventModel $event): OutboxEvent
    {
        return new OutboxEvent(
            id: $event->id,
            aggregateId: $event->aggregate_id,
            payload: $event->payload,
            status: OutboxEventStatus::from($event->status),
            publishedAt: $event->published_at instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($event->published_at)
                : null,
            createdAt: $event->created_at instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($event->created_at)
                : null,
            tries: $event->tries,
            nextRetryAt: $event->next_retry_at instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($event->next_retry_at)
                : null,
        );
    }
}
