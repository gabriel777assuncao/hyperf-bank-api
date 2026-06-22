<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;
use Hyperf\Validation\Middleware\ValidationMiddleware;

Router::get('/health', function () {
    return 'OK';
});

Router::addGroup('/api/v1', function () {
    Router::post('/register', 'App\Auth\Infrastructure\Http\Controller\AuthController@register', [
        'middleware' => [ValidationMiddleware::class],
    ]);
    Router::post('/login', 'App\Auth\Infrastructure\Http\Controller\AuthController@login', [
        'middleware' => [ValidationMiddleware::class],
    ]);
});
