<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Model\User;
use App\Shared\Model\Wallet;
use Domain\Enum\UserType;
use Hyperf\Database\Seeders\Seeder;
use Hyperf\Stringable\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $common = User::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'João Silva',
            'cpf' => '12345678901',
            'email' => 'joao@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'type' => UserType::COMMON->value,
        ]);

        $merchant = User::create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Maria Loja',
            'cpf' => '98765432100',
            'email' => 'maria@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'type' => UserType::MERCHANT->value,
        ]);

        Wallet::create(['id' => (string) Str::uuid(), 'user_id' => $common->id, 'balance' => 1000000]);
        Wallet::create(['id' => (string) Str::uuid(), 'user_id' => $merchant->id, 'balance' => 1000000]);

        echo "2 usuários e suas carteiras criados.\n";
    }
}
