<?php

declare(strict_types=1);

/**
 * @var \Hyperf\Database\Model\Factory $factory
 */

use App\Transaction\Infrastructure\Model\TransactionModel;
use Faker\Generator;
use Ramsey\Uuid\Uuid;

$factory->define(TransactionModel::class, function (Generator $faker) {
    return [
        'id' => (string) Uuid::uuid4(),
        'payer_id' => null,
        'payee_id' => null,
        'value' => $faker->numberBetween(100, 10000),
        'status' => 'completed',
    ];
});

$factory->state(TransactionModel::class, 'completed', ['status' => 'completed']);
$factory->state(TransactionModel::class, 'failed', ['status' => 'failed']);
$factory->state(TransactionModel::class, 'pending', ['status' => 'pending']);
