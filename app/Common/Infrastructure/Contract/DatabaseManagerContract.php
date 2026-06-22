<?php

declare(strict_types=1);

namespace App\Common\Infrastructure\Contract;

interface DatabaseManagerContract
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
