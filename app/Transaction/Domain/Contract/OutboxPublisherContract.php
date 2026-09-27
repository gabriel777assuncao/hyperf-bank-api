<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

use App\Transaction\Domain\Entity\OutboxEvent;

interface OutboxPublisherContract
{
    public function publish(OutboxEvent $event): void;
}
