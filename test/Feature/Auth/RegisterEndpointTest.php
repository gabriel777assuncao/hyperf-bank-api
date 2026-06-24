<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Auth;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Auth\Infrastructure\Http\Controller\AuthController::register
 */
final class RegisterEndpointTest extends TestCase
{
    public function test_register_returns_201_with_token_and_user(): void
    {
        $response = $this->post('/api/v1/register', [
            'full_name' => 'Gabriel Costa',
            'email' => 'gabriel@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response->assertCreated();

        $body = $response->json();
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertNotEmpty($body['data']['token']);
        $this->assertSame('gabriel@example.com', $body['data']['user']['email']);
        $this->assertSame('NORMAL', $body['data']['user']['type']);
        $this->assertSame('529.982.247-25', $body['data']['user']['cpf']);
    }

    public function test_register_shopkeeper_with_cnpj(): void
    {
        $response = $this->post('/api/v1/register', [
            'full_name' => 'Loja SA',
            'email' => 'loja@example.com',
            'password' => 'password123',
            'type' => 'SHOPKEEPER',
            'cnpj' => '11222333000181',
        ]);

        $response->assertCreated();

        $body = $response->json();
        $this->assertSame('SHOPKEEPER', $body['data']['user']['type']);
        $this->assertNotEmpty($body['data']['user']['cnpj']);
        $this->assertNull($body['data']['user']['cpf']);
    }

    public function test_duplicate_cpf_returns_409(): void
    {
        $this->post('/api/v1/register', [
            'full_name' => 'User A',
            'email' => 'usera@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response = $this->post('/api/v1/register', [
            'full_name' => 'User B',
            'email' => 'userb@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response->assertConflict();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_duplicate_email_returns_409(): void
    {
        $this->post('/api/v1/register', [
            'full_name' => 'User A',
            'email' => 'shared@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response = $this->post('/api/v1/register', [
            'full_name' => 'User B',
            'email' => 'shared@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
            'cpf' => '11144477735',
        ]);

        $response->assertConflict();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_missing_document_returns_422_with_validation_shape(): void
    {
        $response = $this->post('/api/v1/register', [
            'full_name' => 'No Doc',
            'email' => 'nodoc@example.com',
            'password' => 'password123',
            'type' => 'NORMAL',
        ]);

        $response->assertUnprocessable();

        $body = $response->json();

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayNotHasKey('error', $body);
    }

    public function test_invalid_type_returns_422(): void
    {
        $response = $this->post('/api/v1/register', [
            'full_name' => 'Bad Type',
            'email' => 'badtype@example.com',
            'password' => 'password123',
            'type' => 'ADMIN',
            'cpf' => '52998224725',
        ]);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('errors', $response->json());
    }
}
