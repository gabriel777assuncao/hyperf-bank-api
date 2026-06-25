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
return [
    'generator' => [
        'amqp' => [
            'consumer' => [
                'namespace' => 'App\Transaction\Infrastructure\Amqp\Consumer',
            ],
            'producer' => [
                'namespace' => 'App\Transaction\Infrastructure\Amqp\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'App\Aspect',
        ],
        'command' => [
            'namespace' => 'App\Command',
        ],
        'controller' => [
            'namespace' => 'App\Common\Infrastructure\Http\Controller',
        ],
        'job' => [
            'namespace' => 'App\Job',
        ],
        'listener' => [
            'namespace' => 'App\Common\Infrastructure\Listener',
        ],
        'middleware' => [
            'namespace' => 'App\Middleware',
        ],
        'Process' => [
            'namespace' => 'App\Processes',
        ],
    ],
];
