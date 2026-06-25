<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Exception;

use App\Common\Domain\Exception\DomainException;
use App\Transaction\Domain\Exception\{AuthorizerUnavailableException, SelfTransferException, TransferNotAuthorizedException, UnauthorizedTransferException};
use App\User\Domain\Exception\{InvalidCredentialsException, UserAlreadyExistsException, UserNotFoundException};
use App\Wallet\Domain\Exception\{InsufficientBalanceException, WalletNotFoundException};
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
            $throwable instanceof WalletNotFoundException => 404,
            $throwable instanceof InvalidCredentialsException => 401,
            $throwable instanceof UserAlreadyExistsException => 409,
            $throwable instanceof SelfTransferException => 422,
            $throwable instanceof UnauthorizedTransferException => 422,
            $throwable instanceof TransferNotAuthorizedException => 422,
            $throwable instanceof InsufficientBalanceException => 422,
            $throwable instanceof AuthorizerUnavailableException => 503,
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
