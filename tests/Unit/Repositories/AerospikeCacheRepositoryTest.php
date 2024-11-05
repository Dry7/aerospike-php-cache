<?php

namespace Tests\Unit\Repositories;

use App\Contracts\AerospikeClient;
use App\Repositories\AerospikeCacheRepository;
use Mockery;
use Tests\TestCase;

class AerospikeCacheRepositoryTest extends TestCase
{
    public function testLoad(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('getFloat')->with($namespace, $set, $userID)->andReturn([$price, $generation])->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->load($userID);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testCreate(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('postFloat')->with($namespace, $set, $userID, $price)->andReturnTrue()->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->create($userID, $price);

        self::assertTrue($actual);
    }

    public function testSaveWhenExists(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('putFloat')->with($namespace, $set, $userID, $price)->andThrow(\Exception::class)->once();
        $client->shouldReceive('postFloat')->with($namespace, $set, $userID, $price)->andReturnTrue()->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->save($userID, $price);

        self::assertTrue($actual);
    }

    public function testSaveWhenNew(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('putFloat')->with($namespace, $set, $userID, $price)->andReturnTrue()->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->save($userID, $price);

        self::assertTrue($actual);
    }

    public function testSaveFailed(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;

        self::expectException(\Exception::class);

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('putFloat')->with($namespace, $set, $userID, $price)->andThrow(\Exception::class)->once();
        $client->shouldReceive('postFloat')->with($namespace, $set, $userID, $price)->andThrow(\Exception::class)->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $repository->save($userID, $price);
    }

    public function testRollbackNew(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;
        $version = 1;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('delete')->with($namespace, $set, $userID)->andReturnTrue()->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->rollback($userID, $price, $version);

        self::assertTrue($actual);
    }

    public function testRollbackOld(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;
        $version = 2;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('putFloat')->with($namespace, $set, $userID, $price, $version)->andReturnTrue()->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->rollback($userID, $price, $version);

        self::assertTrue($actual);
    }

    public function testRollbackOldNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $price = 99.99;
        $version = 2;

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('putFloat')->with($namespace, $set, $userID, $price, $version)->andThrow(\Exception::class)->once();
        $repository = new AerospikeCacheRepository($client, $namespace, $set);
        $actual = $repository->rollback($userID, $price, $version);

        self::assertFalse($actual);
    }
}
