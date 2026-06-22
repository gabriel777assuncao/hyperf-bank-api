<?php

declare(strict_types=1);

namespace App\Common\Infrastructure;

use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use Hyperf\DbConnection\Db;

final class DatabaseManager implements DatabaseManagerContract
{
    public function transaction(callable $callback): mixed
    {
        return Db::transaction($callback);
    }
}
