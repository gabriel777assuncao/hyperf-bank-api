<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Service;

use App\Auth\Domain\Contract\AuthContract;
use App\User\Domain\Entity\User;
use Firebase\JWT\{JWT, Key};
use Hyperf\Contract\ConfigInterface;

final class JwtAuthService implements AuthContract
{
    private string $secret;

    private string $algorithm;

    private int $ttl;

    public function __construct(ConfigInterface $config)
    {
        $this->secret = (string) $config->get('jwt.secret', '');
        $this->algorithm = (string) $config->get('jwt.algorithm', 'HS256');
        $this->ttl = (int) $config->get('jwt.ttl', 3600);
    }

    public function generateToken(User $user): string
    {
        $now = time();

        $payload = [
            'sub' => $user->id(),
            'type' => $user->type()->value,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function resolveUserId(string $token): string
    {
        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));

        return (string) $decoded->sub;
    }
}
