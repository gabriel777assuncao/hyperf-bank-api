<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Common\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected StdoutLoggerInterface $logger
    )
    {
    }

    /**
     * Catches all unhandled exceptions as a 500 fallback.
     *
     * Hyperf's exception dispatcher runs ALL handlers in sequence, passing
     * the response from one to the next. This handler checks if a previous
     * handler (e.g., DomainExceptionHandler) already set a non-200 status.
     * If so, the response is passed through unchanged to avoid overwriting
     * a deliberate error status (401, 404, 409, 422) with a generic 500.
     *
     * @see \App\Common\Exception\Handler\DomainExceptionHandler
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
