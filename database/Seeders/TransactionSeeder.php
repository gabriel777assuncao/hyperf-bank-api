<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shared\Model\Transaction;
use App\Shared\Model\User;
use Hyperf\Database\Seeders\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all()->pluck('id')->toArray();
        $statuses = ['pending', 'completed', 'failed'];

        foreach (range(1, 5) as $i) {
            $payer = $users[array_rand($users)];
            $payee = $users[array_rand($users)];

            while ($payee === $payer) {
                $payee = $users[array_rand($users)];
            }

            Transaction::create([
                'id' => 'txn-' . uniqid(),
                'payer_id' => $payer,
                'payee_id' => $payee,
                'value' => random_int(100, 10000) * 100,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }

        echo "5 transações criadas.\n";
    }
}
