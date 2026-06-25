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
use Hyperf\ExceptionHandler\Listener\ErrorExceptionHandler;
use function Hyperf\Support\env;

$listeners = [
    ErrorExceptionHandler::class,
];

if (env('APP_ENV') === 'testing') {
    $listeners = array_values(array_filter(
        $listeners,
        static fn (string $listener): bool => $listener !== ErrorExceptionHandler::class
    ));
}

return $listeners;
