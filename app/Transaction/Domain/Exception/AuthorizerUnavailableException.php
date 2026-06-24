<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Exception;

use App\Common\Domain\Exception\DomainException;
use RuntimeException;

final class AuthorizerUnavailableException extends RuntimeException implements DomainException
{
    public function __construct(string $message = 'Authorizer service unavailable.')
    {
        parent::__construct($message);
    }
}
