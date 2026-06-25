<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'secret' => env('JWT_SECRET', 'local-dev-secret-change-in-production'),
    'algorithm' => 'HS256',
    'ttl' => (int) env('JWT_TTL', 3600),
];
