<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Transaction;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Application\UseCases\TransferUseCase
 */
final class TransferRulesTest extends TestCase
{
    public function test_self_transfer_returns_422(): void
    {
        $user = $this->createUser();
        $this->fundWallet($user->id, 10000);

        $response = $this->post('/api/v1/transfer', [
            'payer' => $user->id,
            'payee' => $user->id,
            'value' => 50.00,
        ]);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('errors', $response->json());
    }


    public function test_shopkeeper_cannot_send_transfer(): void
    {
        $shopkeeper = $this->createShopkeeper();
        $payee = $this->createUser();

        $response = $this->post('/api/v1/transfer', [
            'payer' => $shopkeeper->id,
            'payee' => $payee->id,
            'value' => 50.00,
        ]);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_insufficient_balance_returns_422_and_persists_failed_transaction(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();

        $this->fundWallet($payer->id, 1000);

        $response = $this->post('/api/v1/transfer', [
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => 100.00,
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('wallets', ['user_id' => $payer->id, 'balance' => 1000]);
        $this->assertDatabaseHas('wallets', ['user_id' => $payee->id, 'balance' => 0]);

        $this->assertDatabaseHas('transactions', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'status' => 'failed',
        ]);

        $this->assertNoOutboxEventRecorded();
    }

    public function test_payee_not_found_returns_404(): void
    {
        $payer = $this->createUser();
        $this->fundWallet($payer->id, 10000);

        $response = $this->post('/api/v1/transfer', [
            'payer' => $payer->id,
            'payee' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'value' => 50.00,
        ]);

        $response->assertNotFound();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_invalid_value_returns_422_validation_shape(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();

        $response = $this->post('/api/v1/transfer', [
            'payer' => $payer->id,
            'payee' => $payee->id,
            'value' => -10,
        ]);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('errors', $response->json());
    }
}
