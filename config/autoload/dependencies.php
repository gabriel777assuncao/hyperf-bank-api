<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use App\Auth\Domain\Contract\AuthContract;
use App\Auth\Infrastructure\Service\JwtAuthService;
use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\Common\Infrastructure\DatabaseManager;
use App\Transaction\Domain\Contract\{AuthorizerContract, NotifierContract, TransactionRepositoryContract, TransferPublisherContract};
use App\Transaction\Infrastructure\Repository\TransactionRepository;
use App\Transaction\Infrastructure\Amqp\Publisher\AmqpTransferPublisher;
use App\Transaction\Infrastructure\Service\{GuzzleAuthorizer, GuzzleNotifier, StubAuthorizer};
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Infrastructure\Repository\UserRepository;
use App\Wallet\Domain\Contract\WalletRepositoryContract;
use App\Wallet\Infrastructure\Repository\WalletRepository;

use function Hyperf\Support\env;

return [
    AuthContract::class => JwtAuthService::class,
    DatabaseManagerContract::class => DatabaseManager::class,


    UserRepositoryContract::class => UserRepository::class,
    WalletRepositoryContract::class => WalletRepository::class,
    TransactionRepositoryContract::class => TransactionRepository::class,
    AuthorizerContract::class => env('APP_ENV', 'dev') === 'dev' ? StubAuthorizer::class : GuzzleAuthorizer::class,
    NotifierContract::class => GuzzleNotifier::class,
    TransferPublisherContract::class => AmqpTransferPublisher::class,
];
