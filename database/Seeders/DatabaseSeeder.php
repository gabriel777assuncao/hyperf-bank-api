<?php

declare(strict_types=1);

use App\User\Domain\Enum\UserType;
use App\User\Infrastructure\Model\UserModel;
use App\Wallet\Infrastructure\Model\WalletModel;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 0');

        UserModel::truncate();
        WalletModel::truncate();

        Db::connection()->getPdo()->exec('SET FOREIGN_KEY_CHECKS = 1');

        $joao = UserModel::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'João Silva',
            'cpf' => '04164106859',
            'email' => 'joao@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'type' => UserType::NORMAL->value,
        ]);

        $maria = UserModel::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Maria Loja',
            'cnpj' => '11222333000181',
            'email' => 'maria@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'type' => UserType::SHOPKEEPER->value,
        ]);

        WalletModel::create(['id' => (string) Str::uuid(), 'user_id' => $joao->id, 'balance' => 1000000]);
        WalletModel::create(['id' => (string) Str::uuid(), 'user_id' => $maria->id, 'balance' => 0]);

        echo "2 usuários e suas carteiras criados.\n";
    }
}