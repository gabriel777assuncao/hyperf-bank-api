<?php

declare(strict_types=1);

/**
 * @var \Hyperf\Database\Model\Factory $factory
 */

use App\Wallet\Infrastructure\Model\WalletModel;
use Faker\Generator;
use Ramsey\Uuid\Uuid;

$factory->define(WalletModel::class, function (Generator $faker) {
    return [
        'id' => (string) Uuid::uuid4(),
        'user_id' => null,
        'balance' => 0,
    ];
});

$factory->state(WalletModel::class, 'funded', function (Generator $faker) {
    return [
        'balance' => $faker->numberBetween(10000, 100000),
    ];
});
