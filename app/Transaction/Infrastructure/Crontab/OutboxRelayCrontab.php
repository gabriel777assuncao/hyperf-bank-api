<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Crontab;

use App\Transaction\Application\UseCases\PublishPendingOutboxEventsUseCase;
use Hyperf\Crontab\Annotation\Crontab;

final class OutboxRelayCrontab
{
    public function __construct(
        private readonly PublishPendingOutboxEventsUseCase $useCase,
    ) {
    }

    #[Crontab(
        rule: '*/5 * * * * *',
            name: 'outbox-relay',
            singleton: true,
            onOneServer: true,
            memo: 'Publishes pending outbox events to AMQP.',
    )]
    public function execute(): void
    {
        $this->useCase->execute();
    }
}
