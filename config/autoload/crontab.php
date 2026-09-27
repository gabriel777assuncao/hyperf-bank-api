<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'enable' => (bool) env('CRONTAB_ENABLE', true),
];
