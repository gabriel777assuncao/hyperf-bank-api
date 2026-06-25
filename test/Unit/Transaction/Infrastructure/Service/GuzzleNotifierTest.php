<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Infrastructure\Service;

use App\Transaction\Infrastructure\Service\GuzzleNotifier;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\ValueObject\{Email, Password};
use GuzzleHttp\{Client, HandlerStack};
use GuzzleHttp\Exception\{ConnectException, RequestException};
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\{Request, Response};
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 * @covers \App\Transaction\Infrastructure\Service\GuzzleNotifier
 */
final class GuzzleNotifierTest extends TestCase
{
    private function makeNotifier(MockHandler $handler): GuzzleNotifier
    {
        $factory = $this->createStub(ClientFactory::class);
        $factory->method('create')->willReturn(
            new Client(['handler' => HandlerStack::create($handler)])
        );

        return new GuzzleNotifier($factory);
    }

    private function payee(): User
    {
        return new User(
            id: '01234567-89ab-cdef-0123-456789abcdef',
            fullName: 'João Silva',
            email: new Email('joao@example.com'),
            password: Password::fromHash('$2y$10$placeholderHashNotVerifiedAnywhereButStored'),
            type: UserType::NORMAL,
        );
    }

    public function test_notify_succeeds_on_2xx_response(): void
    {
        $notifier = $this->makeNotifier(new MockHandler([
            new Response(204),
        ]));

        $notifier->notify($this->payee());

        $this->addToAssertionCount(1);
    }

    public function test_notify_throws_on_4xx_response(): void
    {
        $notifier = $this->makeNotifier(new MockHandler([
            new Response(400, [], (string) json_encode(['error' => 'bad request'])),
        ]));

        $this->expectException(RequestException::class);
        $notifier->notify($this->payee());
    }

    public function test_notify_throws_on_connection_failure(): void
    {
        $notifier = $this->makeNotifier(new MockHandler([
            new ConnectException('Connection refused', new Request('POST', 'https://util.devi.tools/api/v1/notify')),
        ]));

        $this->expectException(ConnectException::class);
        $notifier->notify($this->payee());
    }

    public function test_notify_posts_expected_payload_to_notify_url(): void
    {
        $handler = new MockHandler([
            new Response(204),
        ]);

        $notifier = $this->makeNotifier($handler);
        $payee = $this->payee();
        $notifier->notify($payee);

        $lastRequest = $handler->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $lastRequest);
        self::assertSame('POST', $lastRequest->getMethod());
        self::assertSame('https://util.devi.tools/api/v1/notify', (string) $lastRequest->getUri());

        $payload = json_decode((string) $lastRequest->getBody(), true);
        self::assertSame($payee->id(), $payload['user_id']);
        self::assertSame($payee->email()->toString(), $payload['email']);
    }
}