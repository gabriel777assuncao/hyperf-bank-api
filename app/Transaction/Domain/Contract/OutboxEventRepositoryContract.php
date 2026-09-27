<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

use App\Transaction\Domain\Entity\OutboxEvent;
use DateTimeImmutable;

interface OutboxEventRepositoryContract
{
    public function save(OutboxEvent $event): void;

    /**
     *
     * @return array<OutboxEvent>
     */
    public function findReadyToPublish(int $limit, DateTimeImmutable $now): array;

    public function countPendingNotReady(): int;
}
