<?php

namespace Tests\Unit\Repositories;

use App\Repositories\DBBalanceRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DBBalanceRepositoryTest extends TestCase
{
    public function testLoad(): void
    {
        $userID = 1;
        $balance = 100.00;

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturn($builder);
        $builder->shouldReceive('lockForUpdate')->andReturn($builder);
        $builder->shouldReceive('first')->andReturn((object) ['balance' => $balance]);
        DB::shouldReceive('table')->andReturn($builder)->once();
        $repository = new DBBalanceRepository;
        $actual = $repository->load($userID);

        self::assertEquals($balance, $actual);
    }

    public function testLoadNotFound(): void
    {
        $userID = 1;
        $balance = null;

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturn($builder);
        $builder->shouldReceive('lockForUpdate')->andReturn($builder);
        $builder->shouldReceive('first')->andReturnNull();
        DB::shouldReceive('table')->andReturn($builder)->once();
        $repository = new DBBalanceRepository;
        $actual = $repository->load($userID);

        self::assertEquals($balance, $actual);
    }

    public function testSave(): void
    {
        $userID = 1;
        $balance = 10.00;

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('upsert');
        DB::shouldReceive('table')->andReturn($builder)->once();
        $repository = new DBBalanceRepository;
        $actual = $repository->save($userID, $balance);

        self::assertEquals($balance, $actual);
    }

    public function testTransaction(): void
    {
        $userID = 1;
        $balance = 10.00;

        DB::shouldReceive('transaction')->andReturnTrue()->once();
        $repository = new DBBalanceRepository;
        $actual = $repository->transaction(function () {});

        self::assertEquals($balance, $actual);
    }
}
