<?php

declare(strict_types=1);

use App\Auth\Infrastructure\Http\Controller\AuthController;
use App\Auth\Infrastructure\Http\Middleware\JwtAuthMiddleware;
use App\Common\Infrastructure\Http\Middleware\IdempotencyMiddleware;
use App\Transaction\Infrastructure\Http\Controller\TransferController;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Validation\Middleware\ValidationMiddleware;

Router::get('/health', function () {
    return 'OK';
});

Router::addGroup('/api/v1', function () {
    Router::addGroup('', function () {
        Router::post('/register', [AuthController::class, 'register']);
        Router::post('/login', [AuthController::class, 'login']);
    }, [
        'middleware' => [ValidationMiddleware::class],
    ]);

    Router::post('/transfer', [TransferController::class, 'store'], [
        'middleware' => [
            JwtAuthMiddleware::class,
            IdempotencyMiddleware::class,
            ValidationMiddleware::class,
        ],
    ]);

});