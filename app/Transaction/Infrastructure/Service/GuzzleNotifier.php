<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Service;

use App\Transaction\Domain\Contract\NotifierContract;
use App\User\Domain\Entity\User;
use Hyperf\Guzzle\ClientFactory;

final class GuzzleNotifier implements NotifierContract
{
    private const URL = 'https://util.devi.tools/api/v1/notify';

    private const TIMEOUT = 5.0;

    public function __construct(
        private ClientFactory $clientFactory,
    ) {
    }

    public function notify(User $payee): void
    {
        $client = $this->clientFactory->create(['timeout' => self::TIMEOUT]);

        $client->post(self::URL, [
            'json' => [
                'user_id' => $payee->id(),
                'email' => $payee->email()->toString(),
            ],
        ]);
    }
}