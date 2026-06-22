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

use App\Auth\Infrastructure\JwtAuthService;
use App\Common\Domain\AuthContract;
use App\User\Infrastructure\Contract\UserRepositoryContract;
use App\User\Infrastructure\Repository\UserRepository;

return [
    AuthContract::class => JwtAuthService::class,
    UserRepositoryContract::class => UserRepository::class,
];
