<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure;

use App\Auth\Domain\AuthContract;
use Firebase\JWT\{JWT, Key};
use Hyperf\Contract\ConfigInterface;
use stdClass;

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

    public function encode(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->ttl;

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key($this->secret, $this->algorithm));
    }
}
