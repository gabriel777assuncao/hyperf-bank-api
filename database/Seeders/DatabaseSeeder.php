<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Transaction\Infrastructure\Model\TransactionModel;
use App\User\Infrastructure\Model\UserModel;
use App\Wallet\Infrastructure\Model\WalletModel;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 0');

        TransactionModel::truncate();
        WalletModel::truncate();
        UserModel::truncate();

        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 1');

        (new UserSeeder())->run();
        (new TransactionSeeder())->run();
    }
}
