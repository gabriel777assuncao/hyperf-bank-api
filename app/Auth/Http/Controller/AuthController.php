<?php

declare(strict_types=1);

namespace App\Auth\Http\Controller;

use App\Auth\Application\UseCase\LoginUseCase;
use App\Auth\Application\UseCase\RegisterUseCase;
use App\Auth\Http\Request\LoginRequest;
use App\Auth\Http\Request\RegisterRequest;
use App\Auth\Http\Resource\AuthResponseResource;
use App\Common\Http\AbstractController;
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
