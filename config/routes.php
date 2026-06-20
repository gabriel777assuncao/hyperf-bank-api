<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::get('/health', function () {
    return 'OK';
});

Router::get('/favicon.ico', function () {
    return '';
});
