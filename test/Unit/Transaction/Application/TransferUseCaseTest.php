<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Transaction\Application;

use App\Common\Infrastructure\Contract\DatabaseManagerContract;
use App\Transaction\Application\UseCases\TransferUseCase;
use App\Transaction\Domain\Contract\{AuthorizerContract, TransactionRepositoryContract, TransferPublisherContract};
use App\Transaction\Domain\Enum\TransactionStatus;
use App\Transaction\Domain\Exception\{AuthorizerUnavailableException,
    SelfTransferException,
    TransferNotAuthorizedException,
    UnauthorizedTransferException};
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserType;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\ValueObject\{Cpf, Email, Password};
use App\Wallet\Domain\Contract\WalletRepositoryContract;
use App\Wallet\Domain\Entity\Wallet;
use App\Wallet\Domain\Exception\InsufficientBalanceException;
use App\Transaction\Domain\Entity\Transaction;
use App\Wallet\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @internal
 * @covers \App\Transaction\Application\UseCases\TransferUseCase
 */
final class TransferUseCaseTest extends TestCase
{
    private UserRepositoryContract&MockObject $userRepository;

    private WalletRepositoryContract&MockObject $walletRepository;

    private TransactionRepositoryContract&MockObject $transactionRepository;

    private AuthorizerContract&MockObject $authorizer;

    private DatabaseManagerContract&MockObject $databaseManager;

    private TransferPublisherContract&MockObject $publisher;

    private LoggerInterface&MockObject $logger;

    private TransferUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryContract::class);
        $this->walletRepository = $this->createMock(WalletRepositoryContract::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryContract::class);
        $this->authorizer = $this->createMock(AuthorizerContract::class);
        $this->databaseManager = $this->createMock(DatabaseManagerContract::class);
        $this->publisher = $this->createMock(TransferPublisherContract::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->databaseManager
            ->method('transaction')
            ->willReturnCallback(fn (callable $callback) => $callback());

        $this->useCase = new TransferUseCase(
            $this->userRepository,
            $this->walletRepository,
            $this->transactionRepository,
            $this->authorizer,
            $this->databaseManager,
            $this->publisher,
            $this->logger,
        );
    }

    private function expectFailedTransactionRecorded(): void
    {
        $this->transactionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (Transaction $transaction): bool => $transaction->status() === TransactionStatus::FAILED,
            ));
    }

    public function test_happy_path_transfer_completed(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);
        $payee = $this->makeUser('payee-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', $payee],
            ]);

        $payerWallet = $this->makeWallet('payer-1', 10000);
        $payeeWallet = $this->makeWallet('payee-1', 5000);

        $this->walletRepository->method('findByUserIdForUpdate')
            ->willReturnMap([
                ['payer-1', $payerWallet],
                ['payee-1', $payeeWallet],
            ]);

        $this->walletRepository->expects($this->exactly(2))->method('save');
        $this->transactionRepository->expects($this->once())->method('save');
        $this->publisher->expects($this->once())->method('publishTransferCompleted');

        $result = $this->useCase->execute('payer-1', 'payee-1', new Money(1000));

        $this->assertSame(TransactionStatus::COMPLETED, $result->status());
        $this->assertSame(1000, $result->amount()->toCents());
        $this->assertSame('payer-1', $result->payerId());
        $this->assertSame('payee-1', $result->payeeId());
    }

    public function test_self_transfer_throws_exception(): void
    {
        $this->userRepository->expects($this->never())->method('findById');

        $this->expectException(SelfTransferException::class);

        $this->useCase->execute('same-id', 'same-id', new Money(1000));
    }

    public function test_shopkeeper_cannot_transfer(): void
    {
        $shopkeeper = $this->makeUser('shopkeeper-1', UserType::SHOPKEEPER);

        $this->userRepository->method('findById')
            ->willReturnMap([['shopkeeper-1', $shopkeeper]]);

        $this->authorizer->expects($this->never())->method('authorize');

        $this->expectException(UnauthorizedTransferException::class);

        $this->useCase->execute('shopkeeper-1', 'payee-1', new Money(1000));
    }

    public function test_payer_not_found_throws_exception(): void
    {
        $this->userRepository->method('findById')
            ->willReturnMap([['payer-1', null]]);

        $this->expectException(UserNotFoundException::class);

        $this->useCase->execute('payer-1', 'payee-1', new Money(1000));
    }

    public function test_payee_not_found_throws_exception(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', null],
            ]);

        $this->expectException(UserNotFoundException::class);

        $this->useCase->execute('payer-1', 'payee-1', new Money(1000));
    }

    public function test_authorizer_denied_throws_exception(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);
        $payee = $this->makeUser('payee-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', $payee],
            ]);

        $this->authorizer
            ->method('authorize')
            ->willThrowException(new TransferNotAuthorizedException());

        $this->databaseManager->expects($this->never())->method('transaction');
        $this->expectFailedTransactionRecorded();

        $this->expectException(TransferNotAuthorizedException::class);

        $this->useCase->execute('payer-1', 'payee-1', new Money(1000));
    }

    public function test_authorizer_unavailable_throws_exception(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);
        $payee = $this->makeUser('payee-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', $payee],
            ]);

        $this->authorizer
            ->method('authorize')
            ->willThrowException(new AuthorizerUnavailableException());

        $this->databaseManager->expects($this->never())->method('transaction');
        $this->expectFailedTransactionRecorded();

        $this->expectException(AuthorizerUnavailableException::class);

        $this->useCase->execute('payer-1', 'payee-1', new Money(1000));
    }

    public function test_insufficient_balance_throws_exception(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);
        $payee = $this->makeUser('payee-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', $payee],
            ]);

        $payerWallet = $this->makeWallet('payer-1', 100);
        $payeeWallet = $this->makeWallet('payee-1', 5000);

        $this->walletRepository->method('findByUserIdForUpdate')
            ->willReturnMap([
                ['payer-1', $payerWallet],
                ['payee-1', $payeeWallet],
            ]);

        $this->expectFailedTransactionRecorded();

        $this->expectException(InsufficientBalanceException::class);

        $this->useCase->execute('payer-1', 'payee-1', new Money(10000));
    }

    public function test_publisher_failure_propagates_exception(): void
    {
        $payer = $this->makeUser('payer-1', UserType::NORMAL);
        $payee = $this->makeUser('payee-1', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['payer-1', $payer],
                ['payee-1', $payee],
            ]);

        $payerWallet = $this->makeWallet('payer-1', 10000);
        $payeeWallet = $this->makeWallet('payee-1', 5000);

        $this->walletRepository->method('findByUserIdForUpdate')
            ->willReturnMap([
                ['payer-1', $payerWallet],
                ['payee-1', $payeeWallet],
            ]);

        $this->transactionRepository->expects($this->once())->method('save');

        $this->publisher
            ->method('publishTransferCompleted')
            ->willThrowException(new RuntimeException('AMQP down'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AMQP down');

        $this->useCase->execute('payer-1', 'payee-1', new Money(1000));
    }

    public function test_lock_order_when_payer_id_is_lower(): void
    {
        $payer = $this->makeUser('aaaa-uuid', UserType::NORMAL);
        $payee = $this->makeUser('zzzz-uuid', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['aaaa-uuid', $payer],
                ['zzzz-uuid', $payee],
            ]);

        $callOrder = [];

        $this->walletRepository
            ->expects($this->exactly(2))
            ->method('findByUserIdForUpdate')
            ->willReturnCallback(function (string $userId) use (&$callOrder): Wallet {
                $callOrder[] = $userId;

                return $this->makeWallet($userId, 10000);
            });

        $this->useCase->execute('aaaa-uuid', 'zzzz-uuid', new Money(1000));

        $this->assertSame(['aaaa-uuid', 'zzzz-uuid'], $callOrder);
    }

    public function test_lock_order_when_payer_id_is_higher(): void
    {
        $payer = $this->makeUser('zzzz-uuid', UserType::NORMAL);
        $payee = $this->makeUser('aaaa-uuid', UserType::NORMAL);

        $this->userRepository->method('findById')
            ->willReturnMap([
                ['zzzz-uuid', $payer],
                ['aaaa-uuid', $payee],
            ]);

        $callOrder = [];

        $this->walletRepository
            ->expects($this->exactly(2))
            ->method('findByUserIdForUpdate')
            ->willReturnCallback(function (string $userId) use (&$callOrder): Wallet {
                $callOrder[] = $userId;

                return $this->makeWallet($userId, 10000);
            });

        $this->useCase->execute('zzzz-uuid', 'aaaa-uuid', new Money(1000));

        $this->assertSame(['aaaa-uuid', 'zzzz-uuid'], $callOrder);
    }

    private function makeUser(string $id, UserType $type): User
    {
        return new User(
            id: $id,
            fullName: 'Test User',
            email: new Email('test@example.com'),
            password: new Password('password123'),
            type: $type,
            cpf: new Cpf('52998224725'),
        );
    }

    private function makeWallet(string $userId, int $balanceCents): Wallet
    {
        return new Wallet(
            id: 'wallet-'.$userId,
            userId: $userId,
            balance: new Money($balanceCents),
        );
    }
}
