<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Transaction;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Auth\Infrastructure\Http\Middleware\JwtAuthMiddleware
 */
final class TransferJwtAuthTest extends TestCase
{
    public function test_missing_token_returns_401(): void
    {
        $payee = $this->createUser();

        $response = $this->post(
            '/api/v1/transfer',
            ['payee' => $payee->id, 'value' => 50.00],
        );

        $response->assertUnauthorized();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_malformed_token_returns_401(): void
    {
        $payee = $this->createUser();

        $response = $this->post(
            '/api/v1/transfer',
            ['payee' => $payee->id, 'value' => 50.00],
            ['Authorization' => 'Bearer not-a-valid-jwt'],
        );

        $response->assertUnauthorized();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_valid_token_reaches_transfer_validation(): void
    {
        $user = $this->createUser();

        $response = $this->post(
            '/api/v1/transfer',
            [],
            $this->authHeadersFor($user),
        );

        $response->assertUnprocessable();
    }
}
