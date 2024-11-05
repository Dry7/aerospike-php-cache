<?php

namespace Tests\Unit\Services;

use App\Contracts\BalanceRepository;
use App\Contracts\CacheRepository;
use App\Contracts\Locker;
use App\Repositories\NullBalanceRepository;
use App\Services\BalanceService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    public function testGetWhenCacheExists(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(BalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([$price, 0])->once();
        $service = new BalanceService($repository, $cache, $locker, $ttl);
        $actual = $service->get($userID);

        self::assertEquals($price, $actual);
    }

    public function testGetWhenCacheNotExists(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(BalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0])->once();
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->andReturnTrue()->once();
        $repository->shouldReceive('load')->with($userID)->andReturn($price)->once();
        $cache->shouldReceive('create')->with($userID, $price)->once();
        $locker->shouldReceive('release')->with($userID, Mockery::any())->andReturnTrue()->once();
        $service = new BalanceService($repository, $cache, $locker, $ttl);
        $actual = $service->get($userID);

        self::assertEquals($price, $actual);
    }

    public function testGetWhenAcquireFailed(): void
    {
        $userID = 1;
        $ttl = 5;

        self::expectException(\Exception::class);

        $repository = Mockery::mock(BalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0])->once();
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->andThrow(\Exception::class)->once();
        $service = new BalanceService($repository, $cache, $locker, $ttl);

        $service->get($userID);
    }

    public function testGetWhenLoadFailed(): void
    {
        $userID = 1;
        $ttl = 5;

        self::expectException(\Exception::class);

        $repository = Mockery::mock(BalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0])->once();
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->andReturnTrue()->once();
        $repository->shouldReceive('load')->with($userID)->andThrow(\Exception::class)->once();
        $service = new BalanceService($repository, $cache, $locker, $ttl);

        $service->get($userID);
    }

    public function testSaveWhenBalanceSame(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(BalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([$price, 0])->once();
        $service = new BalanceService($repository, $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertTrue($actual);
    }

    public function testSave(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->once();
        $repository->shouldReceive('save')->with($userID, $price)->once();
        $cache->shouldReceive('save')->with($userID, $price)->once();
        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();
        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertTrue($actual);
    }

    public function testSaveWhenAcquireFailed(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->andThrow(\Exception::class);
        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();

        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertFalse($actual);
    }

    public function testSaveWhenRepositoryFailed(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->once();
        $repository->shouldReceive('save')->with($userID, $price)->once()->andThrow(\Exception::class);
        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();
        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertFalse($actual);
    }

    public function testSaveWhenRepositoryFailedWithRollback(): void
    {
        $userID = 1;
        $oldPrice = 89.99;
        $version = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([$oldPrice, $version]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->once();
        $repository->shouldReceive('save')->with($userID, $price)->once()->andThrow(\Exception::class);
        $cache->shouldReceive('rollback')->with($userID, $oldPrice, $version)->once();
        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();
        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertFalse($actual);
    }

    public function testSaveWhenSaveToCacheFailed(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->once();
        $repository->shouldReceive('save')->with($userID, $price)->once();
        $cache->shouldReceive('save')->with($userID, $price)->once()->andThrow(\Exception::class);
        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();
        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        self::assertFalse($actual);
    }

    public function testSaveNutSleepInsideTransaction(): void
    {
        $userID = 1;
        $price = 99.99;
        $ttl = 5;

        Carbon::setTestNow('Tue Nov 05 2024 13:41:02 GMT+0000');
        $repository = Mockery::mock(NullBalanceRepository::class);
        $cache = Mockery::mock(CacheRepository::class);
        $locker = Mockery::mock(Locker::class);
        $cache->shouldReceive('load')->with($userID)->andReturn([null, 0]);
        $locker->shouldReceive('acquire')->with($userID, Mockery::any())->once();
        $repository->shouldReceive('save')->with($userID, $price)->once()
            ->andReturnUsing(function () {
                Carbon::setTestNow('Tue Nov 05 2024 13:55:02 GMT+0000');

                return true;
            });

        $locker->shouldReceive('release')->with($userID, Mockery::any())->once();
        $service = new BalanceService($repository->makePartial(), $cache, $locker, $ttl);

        $actual = $service->save($userID, $price);

        Carbon::setTestNow();

        self::assertFalse($actual);
    }
}
