<?php

declare(strict_types=1);

namespace HyperfTest\Feature\Abstracts;

use App\Auth\Domain\Contract\AuthContract;
use App\Transaction\Domain\Contract\{AuthorizerContract, TransferPublisherContract};
use App\User\Domain\Contract\UserRepositoryContract;
use App\User\Infrastructure\Model\UserModel;
use App\Wallet\Infrastructure\Model\WalletModel;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\Redis;
use Hyperf\Testing\TestCase as HyperfTestCase;
use HyperfTest\Feature\Support\{FakeAuthorizer, InMemoryTransferPublisher};

abstract class TestCase extends HyperfTestCase
{
    protected InMemoryTransferPublisher $publisher;

    protected FakeAuthorizer $fakeAuthorizer;

    private static InMemoryTransferPublisher $sharedPublisher;

    private static FakeAuthorizer $sharedAuthorizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFakes();
        $this->publisher->reset();
        $this->fakeAuthorizer->reset();
        $this->truncateDatabase();
        $this->flushRedis();
    }

    private function bootFakes(): void
    {
        if (! isset(self::$sharedPublisher)) {
            self::$sharedPublisher = new InMemoryTransferPublisher();
        }
        if (! isset(self::$sharedAuthorizer)) {
            self::$sharedAuthorizer = new FakeAuthorizer();
        }

        $container = ApplicationContext::getContainer();
        $container->set(TransferPublisherContract::class, self::$sharedPublisher);
        $container->set(AuthorizerContract::class, self::$sharedAuthorizer);

        $this->publisher = self::$sharedPublisher;
        $this->fakeAuthorizer = self::$sharedAuthorizer;
    }

    private function truncateDatabase(): void
    {
        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        Db::statement('TRUNCATE TABLE transactions');
        Db::statement('TRUNCATE TABLE wallets');
        Db::statement('TRUNCATE TABLE users');
        Db::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function flushRedis(): void
    {
        ApplicationContext::getContainer()->get(Redis::class)->flushDB();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function createUser(array $overrides = []): UserModel
    {
        return $this->modelFactory->factory(UserModel::class)->create($overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
protected function createShopkeeper(array $overrides = []): UserModel
    {
        return $this->modelFactory->factory(UserModel::class)->state('shopkeeper')->create($overrides);
    }

    protected function fundWallet(string $userId, int $cents): void
    {
        WalletModel::where('user_id', $userId)->update(['balance' => $cents]);
    }

    protected function generateToken(UserModel $user): string
    {
        $container = ApplicationContext::getContainer();
        $userEntity = $container->get(UserRepositoryContract::class)->findById($user->id);

        return $container->get(AuthContract::class)->generateToken($userEntity);
    }

    /** @return array<string, string> */
    protected function authHeadersFor(UserModel $user): array
    {
        return ['Authorization' => 'Bearer '.$this->generateToken($user)];
    }
}
