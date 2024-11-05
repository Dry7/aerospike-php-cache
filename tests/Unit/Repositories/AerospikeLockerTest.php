<?php

namespace Tests\Unit\Repositories;

use App\Contracts\AerospikeClient;
use App\Repositories\AerospikeLocker;
use Mockery;
use Tests\TestCase;

class AerospikeLockerTest extends TestCase
{
    public function testAcquire(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $ttl = 10;
        $token = 'token';

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('post')->with($namespace, $set, $userID, ['strBin' => $token], $ttl)->andReturnTrue()->once();
        $repository = new AerospikeLocker($client, $namespace, $set, $ttl);
        $actual = $repository->acquire($userID, $token);

        self::assertTrue($actual);
    }

    public function testRelease(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $userID = 1;
        $ttl = 10;
        $token = 'token';

        $client = Mockery::mock(AerospikeClient::class);
        $client->shouldReceive('delete')->with($namespace, $set, $userID, null, $token)->andReturnTrue()->once();
        $repository = new AerospikeLocker($client, $namespace, $set, $ttl);
        $actual = $repository->release($userID, $token);

        self::assertTrue($actual);
    }
}
