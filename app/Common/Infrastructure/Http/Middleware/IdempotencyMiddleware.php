<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Http\Middleware;

use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Redis\Redis;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final class IdempotencyMiddleware implements MiddlewareInterface
{
    private const KEY_PREFIX = 'idempotency:';

    private const PROCESSING = 'processing';

    private const TTL = 86400;

    public function __construct(
        private Redis $redis,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $request->getHeaderLine('Idempotency-Key');

        if ($key === '') {
            return $handler->handle($request);
        }

        $redisKey = self::KEY_PREFIX.$key;

        $cached = $this->resolveCached($redisKey);
        if ($cached !== null) {
            return $cached;
        }

        if (! $this->acquireLock($redisKey)) {
            $cached = $this->resolveCached($redisKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $response = $handler->handle($request);
        $this->persist($redisKey, $response);

        return $response;
    }

    private function resolveCached(string $redisKey): ?ResponseInterface
    {
        $value = $this->redis->get($redisKey);

        if ($value === self::PROCESSING) {
            return $this->jsonResponse(409, ['error' => 'Request with this Idempotency-Key is already in progress.']);
        }

        if ($value !== false) {
            $data = json_decode($value, true);

            return $this->jsonResponse(
                $data['status'] ?? 200,
                $data['body'] ?? '',
                ['X-Idempotent-Replay' => 'true'],
                raw: true,
            );
        }

        return null;
    }

    private function acquireLock(string $redisKey): bool
    {
        return $this->redis->set($redisKey, self::PROCESSING, ['NX', 'EX' => self::TTL]) !== false;
    }

    private function persist(string $redisKey, ResponseInterface $response): void
    {
        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            $this->redis->del($redisKey);

            return;
        }

        $this->redis->set(
            $redisKey,
            json_encode([
                'status' => $status,
                'body' => (string) $response->getBody(),
            ]),
            ['EX' => self::TTL],
        );
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|string $body
     */
    private function jsonResponse(int $status, array|string $body, array $headers = [], bool $raw = false): ResponseInterface
    {
        $payload = $raw ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);

        $response = new Response();
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status)
            ->withBody(new SwooleStream($payload));

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
