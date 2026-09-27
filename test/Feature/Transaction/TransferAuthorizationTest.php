<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Transaction;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Application\UseCases\TransferUseCase
 */
final class TransferAuthorizationTest extends TestCase
{
    public function test_authorizer_denial_returns_422_and_persists_failed_transaction(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();
        $this->fundWallet($payer->id, 50000);

        $this->fakeAuthorizer->deny();

        $response = $this->post('/api/v1/transfer', [
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => 100.00,
        ]);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('error', $response->json());

        $this->assertDatabaseHas('wallets', ['user_id' => $payer->id, 'balance' => 50000]);

        $this->assertDatabaseHas('transactions', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'status' => 'failed',
        ]);

        $this->assertNoOutboxEventRecorded();
    }

    public function test_authorizer_unavailable_returns_503_and_persists_failed_transaction(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();
        $this->fundWallet($payer->id, 50000);

        $this->fakeAuthorizer->makeUnavailable();

        $response = $this->post('/api/v1/transfer', [
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => 100.00,
        ]);

        $response->assertStatus(503);
        $this->assertArrayHasKey('error', $response->json());

        $this->assertDatabaseHas('wallets', ['user_id' => $payer->id, 'balance' => 50000]);

        $this->assertDatabaseHas('transactions', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'status' => 'failed',
        ]);

        $this->assertNoOutboxEventRecorded();
    }
}
