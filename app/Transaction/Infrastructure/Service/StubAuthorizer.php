<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Service;

use App\Transaction\Domain\Contract\AuthorizerContract;

/**
 * Dev-only stub authorizer that always authorizes transfers.
 * Used automatically when APP_ENV=dev to keep the happy-path deterministic
 * (the external mock at util.devi.tools denies ~50% of calls randomly).
 * In any other environment, GuzzleAuthorizer (real external call) is used.
 */
final class StubAuthorizer implements AuthorizerContract
{
    public function authorize(): void
    {
    }
}