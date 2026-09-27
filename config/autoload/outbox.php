<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'max_tries' => (int) env('OUTBOX_MAX_TRIES', 5),
    'max_delay_seconds' => (int) env('OUTBOX_MAX_DELAY_SECONDS', 60),
    'batch_size' => (int) env('OUTBOX_BATCH_SIZE', 100),
];
