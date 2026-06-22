<?php

declare(strict_types=1);

namespace App\Common\Exception\Handler;

use App\User\Domain\Exception\{DomainException, InvalidCredentialsException, UserAlreadyExistsException, UserNotFoundException};
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class DomainExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $status = match (true) {
            $throwable instanceof UserNotFoundException => 404,
            $throwable instanceof InvalidCredentialsException => 401,
            $throwable instanceof UserAlreadyExistsException => 409,
            $throwable instanceof InvalidArgumentException => 422,
            $throwable instanceof ValidationException => 422,
            default => 500,
        };

        if ($throwable instanceof ValidationException) {
            $body = json_encode([
                'message' => 'The given data was invalid.',
                'errors' => $throwable->errors(),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $body = json_encode([
                'error' => $throwable->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status)
            ->withBody(new SwooleStream($body));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof DomainException
            || $throwable instanceof InvalidArgumentException
            || $throwable instanceof ValidationException;
    }
}
