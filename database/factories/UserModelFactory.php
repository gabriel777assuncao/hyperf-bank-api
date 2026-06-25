<?php

declare(strict_types=1);

/**
 * @var \Hyperf\Database\Model\Factory $factory
 */

use App\User\Domain\Enum\UserType;
use App\User\Infrastructure\Model\UserModel;
use App\Wallet\Infrastructure\Model\WalletModel;
use Faker\Generator;
use Ramsey\Uuid\Uuid;

/**
 * Generate a CPF (11 digits) with valid check-digits accepted by
 * App\User\Domain\ValueObject\Cpf. Never returns an all-equal-digit string.
 */
$validCpf = static function (): string {
    do {
        $digits = '';
        for ($i = 0; $i < 9; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $digits[$i] * (10 - $i);
        }
        $d1 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        $digits .= (string) $d1;
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $digits[$i] * (11 - $i);
        }
        $d2 = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        $digits .= (string) $d2;
    } while (preg_match('/^(\d)\1{10}$/', $digits));

    return $digits;
};

/**
 * Generate a CNPJ (14 digits) with valid check-digits accepted by
 * App\User\Domain\ValueObject\Cnpj. Never returns an all-equal-digit string.
 */
$validCnpj = static function (): string {
    do {
        $digits = '';
        for ($i = 0; $i < 12; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $weights) {
            $sum = 0;
            foreach ($weights as $i => $w) {
                $sum += (int) $digits[$i] * $w;
            }
            $digits .= (string) (($sum % 11) < 2 ? 0 : 11 - ($sum % 11));
        }
    } while (preg_match('/^(\d)\1{13}$/', $digits));

    return $digits;
};

$factory->define(UserModel::class, function (Generator $faker) use ($validCpf) {
    return [
        'id' => (string) Uuid::uuid4(),
        'full_name' => $faker->name(),
        'cpf' => $validCpf(),
        'cnpj' => null,
        'email' => $faker->unique()->safeEmail(),
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'type' => UserType::NORMAL->value,
    ];
});

$factory->state(UserModel::class, 'shopkeeper', function (Generator $faker) use ($validCnpj) {
    return [
        'cpf' => null,
        'cnpj' => $validCnpj(),
        'type' => UserType::SHOPKEEPER->value,
    ];
});

$factory->afterCreating(UserModel::class, function (UserModel $user) {
    WalletModel::create([
        'id' => (string) Uuid::uuid4(),
        'user_id' => $user->id,
        'balance' => 0,
    ]);
});
