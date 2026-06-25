<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Service;

use App\Transaction\Domain\Contract\AuthorizerContract;
use App\Transaction\Domain\Exception\{AuthorizerUnavailableException, TransferNotAuthorizedException};
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\ClientFactory;

final class GuzzleAuthorizer implements AuthorizerContract
{
    private const URL = 'https://util.devi.tools/api/v2/authorize';

    private const TIMEOUT = 5.0;

    public function __construct(
        private ClientFactory $clientFactory,
    ) {
    }

    public function authorize(): void
    {
        $client = $this->clientFactory->create([
            'timeout' => self::TIMEOUT,
            'http_errors' => false,
            'connect_timeout' => self::TIMEOUT,
        ]);

        try {
            $response = $client->get(self::URL);
        } catch (GuzzleException) {
            throw new AuthorizerUnavailableException();
        }

        $body = json_decode((string) $response->getBody(), true);

        if (! is_array($body) || ! array_key_exists('authorization', $body['data'] ?? [])) {
            throw new AuthorizerUnavailableException();
        }

        if ($body['data']['authorization'] !== true) {
            throw new TransferNotAuthorizedException();
        }
    }
}
