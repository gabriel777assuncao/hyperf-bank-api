<?php

declare(strict_types=1);

namespace App\Common\Domain;

use stdClass;

interface AuthContract
{
    public function encode(array $payload): string;

    public function decode(string $token): stdClass;
}
