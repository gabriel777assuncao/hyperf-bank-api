<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Infrastructure\Service;

use App\Transaction\Domain\Exception\{AuthorizerUnavailableException, TransferNotAuthorizedException};
use App\Transaction\Infrastructure\Service\GuzzleAuthorizer;
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Psr7\{Request, Response};
use GuzzleHttp\Handler\MockHandler;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 * @covers \App\Transaction\Infrastructure\Service\GuzzleAuthorizer
 */
final class GuzzleAuthorizerTest extends TestCase
{
    private function makeAuthorizer(MockHandler $handler): GuzzleAuthorizer
    {
        $factory = $this->createStub(ClientFactory::class);
        $factory->method('create')->willReturn(
            new Client(['handler' => HandlerStack::create($handler)])
        );

        return new GuzzleAuthorizer($factory);
    }

    public function test_authorize_succeeds_when_response_authorization_is_true(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new Response(200, [], (string) json_encode(['data' => ['authorization' => true]])),
        ]));

        $authorizer->authorize();

        $this->addToAssertionCount(1);
    }

    public function test_authorize_throws_not_authorized_when_authorization_is_false(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new Response(200, [], (string) json_encode(['data' => ['authorization' => false]])),
        ]));

        $this->expectException(TransferNotAuthorizedException::class);
        $authorizer->authorize();
    }

    public function test_authorize_throws_unavailable_on_connection_failure(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new \GuzzleHttp\Exception\ConnectException('Connection refused', new Request('GET', 'https://util.devi.tools/api/v2/authorize')),
        ]));

        $this->expectException(AuthorizerUnavailableException::class);
        $authorizer->authorize();
    }

    public function test_authorize_throws_unavailable_on_invalid_json_body(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new Response(200, [], 'not-json'),
        ]));

        $this->expectException(AuthorizerUnavailableException::class);
        $authorizer->authorize();
    }

    public function test_authorize_throws_unavailable_when_data_key_is_missing(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new Response(200, [], (string) json_encode(['message' => 'nope'])),
        ]));

        $this->expectException(AuthorizerUnavailableException::class);
        $authorizer->authorize();
    }

    public function test_authorize_throws_unavailable_on_5xx_response(): void
    {
        $authorizer = $this->makeAuthorizer(new MockHandler([
            new Response(500, [], (string) json_encode(['error' => 'internal'])),
        ]));

        $this->expectException(AuthorizerUnavailableException::class);
        $authorizer->authorize();
    }

    public function test_authorize_uses_expected_url_and_timeout(): void
    {
        $handler = new MockHandler([
            new Response(200, [], (string) json_encode(['data' => ['authorization' => true]])),
        ]);

        $authorizer = $this->makeAuthorizer($handler);
        $authorizer->authorize();

        $lastRequest = $handler->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $lastRequest);
        self::assertSame('GET', $lastRequest->getMethod());
        self::assertSame('https://util.devi.tools/api/v2/authorize', (string) $lastRequest->getUri());
    }
}