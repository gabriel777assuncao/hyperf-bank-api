<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Transaction;

use App\Transaction\Domain\Contract\OutboxEventRepositoryContract;
use App\Transaction\Domain\Entity\{OutboxEvent, Transaction};
use App\Transaction\Domain\Enum\TransactionStatus;
use App\Transaction\Infrastructure\Model\{OutboxEventModel, TransactionModel};
use App\Wallet\Domain\ValueObject\Money;
use DateTimeImmutable;
use Hyperf\Context\ApplicationContext;
use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Infrastructure\Repository\OutboxEventRepository::findReadyToPublish
 */
final class OutboxRetryQueryTest extends TestCase
{
    public function test_find_ready_to_publish_only_returns_events_whose_retry_window_is_due(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();

        $transactionId = '99999999-9999-9999-9999-999999999999';
        TransactionModel::query()->create([
            'id' => $transactionId,
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'value' => 10000,
            'status' => 'completed',
        ]);

        $now = new DateTimeImmutable();

        $dueId = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $neverRetriedId = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
        $futureId = 'cccccccc-cccc-cccc-cccc-cccccccccccc';

        $this->createEvent($dueId, $transactionId, $payee->id, $now->modify('-10 seconds'));
        $this->createEvent($neverRetriedId, $transactionId, $payee->id, null);
        $this->createEvent($futureId, $transactionId, $payee->id, $now->modify('+300 seconds'));

        $repository = ApplicationContext::getContainer()->get(OutboxEventRepositoryContract::class);
        $ready = $repository->findReadyToPublish(100, $now);

        $readyIds = array_map(static fn ($event): string => $event->id(), $ready);

        $this->assertCount(2, $ready);
        $this->assertContains($dueId, $readyIds);
        $this->assertContains($neverRetriedId, $readyIds);
        $this->assertNotContains($futureId, $readyIds);
    }

    public function test_save_persists_tries_and_next_retry_at(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();

        $transactionId = '88888888-8888-8888-8888-888888888888';
        TransactionModel::query()->create([
            'id' => $transactionId,
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'value' => 10000,
            'status' => 'completed',
        ]);

        $repository = ApplicationContext::getContainer()->get(OutboxEventRepositoryContract::class);
        $eventId = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

        $transaction = new Transaction(
            id: $transactionId,
            payerId: $payer->id,
            payeeId: $payee->id,
            amount: new Money(10000),
            status: TransactionStatus::COMPLETED,
        );

        $event = OutboxEvent::forCompletedTransfer($eventId, $transaction);
        $repository->save($event);

        $event->registerFailure(new DateTimeImmutable(), 5, 60);
        $repository->save($event);

        $model = OutboxEventModel::query()->findOrFail($eventId);

        $this->assertSame(1, $model->tries);
        $this->assertSame('pending', $model->status);
        $this->assertNotNull($model->next_retry_at);
    }

    private function createEvent(
        string $id,
        string $aggregateId,
        string $payeeId,
        ?DateTimeImmutable $nextRetryAt,
    ): void {
        OutboxEventModel::query()->create([
            'id' => $id,
            'aggregate_id' => $aggregateId,
            'status' => 'pending',
            'tries' => $nextRetryAt === null ? 0 : 1,
            'next_retry_at' => $nextRetryAt,
            'payload' => ['payee_id' => $payeeId],
        ]);
    }
}
