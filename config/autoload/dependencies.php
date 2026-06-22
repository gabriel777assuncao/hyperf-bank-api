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

use App\Auth\Infrastructure\Services\JwtAuthService;
use App\Common\Contract\AuthContract;
use App\User\Infrastructure\Contract\UserRepositoryContract;
use App\User\Infrastructure\Repository\UserRepository;

return [
    AuthContract::class => JwtAuthService::class,
    UserRepositoryContract::class => UserRepository::class,
];
