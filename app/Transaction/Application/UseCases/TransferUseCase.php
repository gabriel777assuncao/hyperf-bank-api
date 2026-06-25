<?php

declare(strict_types=1);

namespace App\Transaction\Application\UseCases;

use App\Common\Domain\Exception\DomainException;
use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\Transaction\Domain\Contract\{AuthorizerContract, TransactionRepositoryContract, TransferPublisherContract};
use App\Transaction\Domain\Entity\Transaction;
use App\Transaction\Domain\Enum\TransactionStatus;
use App\Transaction\Domain\Exception\UnauthorizedTransferException;
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Domain\Exception\UserNotFoundException;
use App\Wallet\Domain\Contract\WalletRepositoryContract;
use App\Wallet\Domain\Entity\Wallet;
// Certifique-se de criar ou usar uma exceção similar
use App\Wallet\Domain\ValueObject\Money;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final readonly class TransferUseCase
{
    public function __construct(
        private UserRepositoryContract $userRepository,
        private WalletRepositoryContract $walletRepository,
        private TransactionRepositoryContract $transactionRepository,
        private AuthorizerContract $authorizer,
        private DatabaseManagerContract $databaseManager,
        private TransferPublisherContract $publisher,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(string $payerId, string $payeeId, Money $amount): Transaction
    {
        $payer = $this->userRepository->findById($payerId);
        
        if ($payer === null) {
            throw new UserNotFoundException(sprintf('User with id "%s" not found.', $payerId));
        }

        if (! $payer->canTransfer()) {
            throw new UnauthorizedTransferException($payer->id());
        }

        $payee = $this->userRepository->findById($payeeId);
        
        if ($payee === null) {
            throw new UserNotFoundException(sprintf('User with id "%s" not found.', $payeeId));
        }

        $transaction = new Transaction(
                id: (string) Uuid::uuid4(),
                payerId: $payerId,
                payeeId: $payeeId,
                amount: $amount,
                status: TransactionStatus::PENDING,
        );

        try {
            $this->authorizer->authorize();

            $this->databaseManager->transaction(function () use ($payerId, $amount, $transaction): void {
                [$firstWallet, $secondWallet] = $this->lockWalletsOrdered($payerId, $transaction->payeeId());

                $payerWallet = $payerId === $firstWallet->userId() ? $firstWallet : $secondWallet;
                $payeeWallet = $payerId === $firstWallet->userId() ? $secondWallet : $firstWallet;

                $payerWallet->debit($amount);
                $payeeWallet->credit($amount);

                $this->walletRepository->save($payerWallet);
                $this->walletRepository->save($payeeWallet);

                $transaction->markAsCompleted();
                $this->transactionRepository->save($transaction);
            });
        } catch (DomainException $exception) {
            $this->recordFailure($transaction);

            throw $exception;
        }

        $this->publisher->publishTransferCompleted($transaction->id(), $payeeId);

        return $transaction;
    }

    private function recordFailure(Transaction $transaction): void
    {
        $transaction->markAsFailed();

        try {
            $this->transactionRepository->save($transaction);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                    'Failed to record FAILED transaction "%s": %s',
                    $transaction->id(),
                    $exception->getMessage(),
            ));
        }
    }

    /**
     * @return array{Wallet, Wallet}
     */
    private function lockWalletsOrdered(string $payerId, string $payeeId): array
    {
        [$firstId, $secondId] = strcmp($payerId, $payeeId) < 0
            ? [$payerId, $payeeId]
            : [$payeeId, $payerId];

        $firstWallet = $this->walletRepository->findByUserIdForUpdate($firstId);
        $secondWallet = $this->walletRepository->findByUserIdForUpdate($secondId);

        return [$firstWallet, $secondWallet];
    }
}
