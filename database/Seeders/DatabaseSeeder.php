<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Model\Transaction;
use App\Shared\Model\User;
use App\Shared\Model\Wallet;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 0');

        Transaction::truncate();
        Wallet::truncate();
        User::truncate();

        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 1');

        (new UserSeeder())->run();
        (new TransactionSeeder())->run();
    }
}
