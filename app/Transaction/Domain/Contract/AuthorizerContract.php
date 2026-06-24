<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

interface AuthorizerContract
{
    public function authorize(): void;
}
