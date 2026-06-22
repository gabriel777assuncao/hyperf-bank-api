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

use App\Auth\Domain\AuthContract;
use App\Auth\Infrastructure\JwtAuthService;
use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\Common\Infrastructure\DatabaseManager;
use App\User\Infrastructure\Contract\UserRepositoryContract;
use App\User\Infrastructure\Repository\UserRepository;
use App\Wallet\Infrastructure\Contract\WalletRepositoryContract;
use App\Wallet\Infrastructure\Repository\WalletRepository;

return [
    AuthContract::class => JwtAuthService::class,
    DatabaseManagerContract::class => DatabaseManager::class,


    UserRepositoryContract::class => UserRepository::class,
    WalletRepositoryContract::class => WalletRepository::class,
];
