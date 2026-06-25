<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Http\Controller;

use App\Common\Infrastructure\Http\AbstractController;
use App\Transaction\Application\UseCases\TransferUseCase;
use App\Transaction\Infrastructure\Http\Request\TransferRequest;
use App\Transaction\Infrastructure\Http\Resource\TransferResource;
use App\Wallet\Domain\ValueObject\Money;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class TransferController extends AbstractController
{
    public function __construct(
        private readonly TransferUseCase $transferUseCase,
    ) {
    }

    public function store(TransferRequest $request): PsrResponseInterface
    {
        $amount = new Money((int) round((float) $request->input('value') * 100));

        $transaction = $this->transferUseCase->execute(
            payerId: (string) $request->input('payer'),
            payeeId: (string) $request->input('payee'),
            amount: $amount,
        );

        return $this->response->json(
            TransferResource::make($transaction)->toResponse()
        )->withStatus(201);
    }
}
