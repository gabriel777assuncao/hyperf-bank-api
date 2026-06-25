<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Support;

use App\Transaction\Domain\Contract\AuthorizerContract;
use App\Transaction\Domain\Exception\{AuthorizerUnavailableException, TransferNotAuthorizedException};

final class FakeAuthorizer implements AuthorizerContract
{
    private string $mode = 'authorize';

    public function authorize(): void
    {
        match ($this->mode) {
            'deny' => throw new TransferNotAuthorizedException(),
            'unavailable' => throw new AuthorizerUnavailableException(),
            default => null,
        };
    }

    public function deny(): void
    {
        $this->mode = 'deny';
    }

    public function makeUnavailable(): void
    {
        $this->mode = 'unavailable';
    }

    public function reset(): void
    {
        $this->mode = 'authorize';
    }
}
