<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Transaction;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Transaction\Infrastructure\Http\Controller\TransferController::store
 */
final class TransferHappyPathTest extends TestCase
{
    public function test_transfer_moves_balances_and_persists_completed_transaction(): void
    {
        $payer = $this->createUser();
        $payee = $this->createUser();

        $this->fundWallet($payer->id, 50000);

        $response = $this->post(
            '/api/v1/transfer',
            ['payee' => $payee->id, 'value' => 100.00],
            $this->authHeadersFor($payer),
        );

        $response->assertCreated();

        $body = $response->json();
        $this->assertSame('completed', $body['data']['status']);
        $this->assertSame(100.0, $body['data']['value']);
        $this->assertSame($payer->id, $body['data']['payer_id']);
        $this->assertSame($payee->id, $body['data']['payee_id']);

        $this->assertDatabaseHas('wallets', ['user_id' => $payer->id, 'balance' => 40000]);
        $this->assertDatabaseHas('wallets', ['user_id' => $payee->id, 'balance' => 10000]);

        $this->assertDatabaseHas('transactions', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'value' => 10000,
            'status' => 'completed',
        ]);

        $this->assertCount(1, $this->publisher->published);
        $this->assertSame($payee->id, $this->publisher->published[0]['payeeId']);
    }
}
