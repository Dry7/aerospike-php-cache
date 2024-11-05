<?php

namespace Tests\Unit\Repositories;

use App\Contracts\AerospikeClient;
use App\Repositories\AerospikeLocker;
use App\Repositories\DBBalanceRepository;
use App\Repositories\NullBalanceRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class NullBalanceRepositoryTest extends TestCase
{
    public function testLoad(): void
    {
        $userID = 1;

        $repository = new NullBalanceRepository();
        $actual = $repository->load($userID);

        self::assertNull($actual);
    }

    public function testSave(): void
    {
        $userID = 1;
        $balance = 10.00;

        $repository = new NullBalanceRepository();
        $actual = $repository->save($userID, $balance);

        self::assertTrue($actual);
    }

    public function testTransaction(): void
    {
        $repository = new DBBalanceRepository();
        $actual = $repository->transaction(function () { return true; });

        self::assertTrue($actual);
    }
}
