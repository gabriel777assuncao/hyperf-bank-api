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
use App\Common\Infrastructure\Exception\{AppExceptionHandler, DomainExceptionHandler};
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;

return [
    'handler' => [
        'http' => [
            DomainExceptionHandler::class,
            HttpExceptionHandler::class,
            AppExceptionHandler::class,
        ],
    ],
];
