<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::get('/health', 'App\Controller\HealthController@check');

Router::get('/favicon.ico', function () {
    return '';
});
