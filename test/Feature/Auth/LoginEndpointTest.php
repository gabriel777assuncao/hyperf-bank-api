<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Auth;

use HyperfTest\Feature\Abstracts\TestCase;

/**
 * @internal
 * @covers \App\Auth\Infrastructure\Http\Controller\AuthController::login
 */
final class LoginEndpointTest extends TestCase
{
    public function test_valid_credentials_return_token_and_user(): void
    {
        $this->post('/api/v1/register', [
            'full_name' => 'Gabriel Costa',
            'email' => 'gabriel@example.com',
            'password' => 'secret123!',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response = $this->post('/api/v1/login', [
            'document' => '529.982.247-25',
            'password' => 'secret123!',
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertNotEmpty($body['data']['token']);
        $this->assertSame('gabriel@example.com', $body['data']['user']['email']);
    }

    public function test_wrong_password_returns_401(): void
    {
        $this->post('/api/v1/register', [
            'full_name' => 'Gabriel Costa',
            'email' => 'gabriel@example.com',
            'password' => 'correct-password',
            'type' => 'NORMAL',
            'cpf' => '52998224725',
        ]);

        $response = $this->post('/api/v1/login', [
            'document' => '529.982.247-25',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_unknown_document_returns_404(): void
    {
        // Note: returning 404 for an unknown document exposes user existence (user enumeration).
        // Ideal fix: LoginUseCase should catch UserNotFoundException and rethrow as InvalidCredentialsException.
        $response = $this->post('/api/v1/login', [
            'document' => '000.000.000-00',
            'password' => 'any-password',
        ]);

        $response->assertNotFound();
        $this->assertArrayHasKey('error', $response->json());
    }

    public function test_missing_fields_returns_422(): void
    {
        $response = $this->post('/api/v1/login', []);

        $response->assertUnprocessable();
        $this->assertArrayHasKey('errors', $response->json());
    }
}
