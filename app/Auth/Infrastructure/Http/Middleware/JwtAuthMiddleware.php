<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use App\Common\Domain\AuthContract;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Throwable;

final class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthContract $auth,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized('Missing or malformed authorization header.');
        }

        $token = substr($header, 7);

        try {
            $decoded = $this->auth->decode($token);
        } catch (Throwable) {
            return $this->unauthorized('Invalid or expired token.');
        }

        $request = $request
            ->withAttribute('user_id', $decoded->sub)
            ->withAttribute('user_type', $decoded->type);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $body = json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);

        $response = new \Hyperf\HttpMessage\Server\Response();

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401)
            ->withBody(new SwooleStream($body));
    }
}
