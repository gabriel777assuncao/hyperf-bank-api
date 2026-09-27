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
use App\Transaction\Application\UseCases\PublishPendingOutboxEventsUseCase;
use App\Transaction\Domain\Contract\{AuthorizerContract, NotifierContract, OutboxEventRepositoryContract, OutboxPublisherContract, TransactionRepositoryContract};
use App\Transaction\Infrastructure\Amqp\Publisher\AmqpOutboxPublisher;
use App\Transaction\Infrastructure\Repository\{OutboxEventRepository, TransactionRepository};
use App\Transaction\Infrastructure\Service\{GuzzleAuthorizer, GuzzleNotifier, StubAuthorizer};
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Infrastructure\Repository\UserRepository;
use App\Wallet\Domain\Contract\WalletRepositoryContract;
use App\Wallet\Infrastructure\Repository\WalletRepository;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function Hyperf\Support\env;

return [
    AuthContract::class => JwtAuthService::class,
    DatabaseManagerContract::class => DatabaseManager::class,
    LoggerInterface::class => fn (ContainerInterface $c) => $c->get(LoggerFactory::class)->get('default'),


    UserRepositoryContract::class => UserRepository::class,
    WalletRepositoryContract::class => WalletRepository::class,
    TransactionRepositoryContract::class => TransactionRepository::class,
    OutboxEventRepositoryContract::class => OutboxEventRepository::class,
    AuthorizerContract::class => env('APP_ENV', 'dev') === 'dev' ? StubAuthorizer::class : GuzzleAuthorizer::class,
    NotifierContract::class => GuzzleNotifier::class,
    OutboxPublisherContract::class => AmqpOutboxPublisher::class,
    PublishPendingOutboxEventsUseCase::class => function (ContainerInterface $c): PublishPendingOutboxEventsUseCase {
        $config = $c->get(ConfigInterface::class);

        return new PublishPendingOutboxEventsUseCase(
            $c->get(OutboxEventRepositoryContract::class),
            $c->get(OutboxPublisherContract::class),
            $c->get(LoggerInterface::class),
            (int) $config->get('outbox.max_tries', 5),
            (int) $config->get('outbox.max_delay_seconds', 60),
            (int) $config->get('outbox.batch_size', 100),
        );
    },
];
