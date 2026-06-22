<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\User\Domain\Enum\UserType;
use App\User\Infrastructure\Model\UserModel;
use App\Wallet\Infrastructure\Model\WalletModel;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\Stringable\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $normal = UserModel::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'João Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'type' => UserType::NORMAL->value,
        ]);

        $shopkeeper = UserModel::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Maria Loja',
            'cnpj' => '11222333000181',
            'email' => 'maria@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'type' => UserType::SHOPKEEPER->value,
        ]);

        WalletModel::create(['id' => (string) Str::uuid(), 'user_id' => $normal->id, 'balance' => 1000000]);
        WalletModel::create(['id' => (string) Str::uuid(), 'user_id' => $shopkeeper->id, 'balance' => 1000000]);

        echo "2 usuários e suas carteiras criados.\n";
    }
}
