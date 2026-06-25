<?php

declare(strict_types=1);

namespace App\Transaction\Domain\Contract;

use App\User\Domain\Entity\User;

interface NotifierContract
{
    public function notify(User $payee): void;
}
