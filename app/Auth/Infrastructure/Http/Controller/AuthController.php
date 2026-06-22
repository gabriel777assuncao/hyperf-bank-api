<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\LoginUseCase;
use App\Auth\Application\RegisterUseCase;
use App\Auth\Infrastructure\Http\Request\LoginRequest;
use App\Auth\Infrastructure\Http\Request\RegisterRequest;
use App\Auth\Infrastructure\Http\Resource\AuthResponseResource;
use App\Common\Infrastructure\Http\AbstractController;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private RegisterUseCase $registerUseCase,
        private LoginUseCase $loginUseCase,
    ) {
    }

    public function register(RegisterRequest $request): PsrResponseInterface
    {
        $result = $this->registerUseCase->execute($request->validated());

        return $this->response->json(
            AuthResponseResource::make($result)->toResponse()
        )->withStatus(201);
    }

    public function login(LoginRequest $request): PsrResponseInterface
    {
        $result = $this->loginUseCase->execute($request->validated());

        return $this->response->json(
            AuthResponseResource::make($result)->toResponse()
        );
    }
}
