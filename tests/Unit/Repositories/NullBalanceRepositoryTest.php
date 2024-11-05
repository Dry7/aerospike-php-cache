<?php

namespace Tests\Unit\Repositories;

use App\Repositories\DBBalanceRepository;
use App\Repositories\NullBalanceRepository;
use Tests\TestCase;

class NullBalanceRepositoryTest extends TestCase
{
    public function testLoad(): void
    {
        $userID = 1;

        $repository = new NullBalanceRepository;
        $actual = $repository->load($userID);

        self::assertNull($actual);
    }

    public function testSave(): void
    {
        $userID = 1;
        $balance = 10.00;

        $repository = new NullBalanceRepository;
        $actual = $repository->save($userID, $balance);

        self::assertTrue($actual);
    }

    public function testTransaction(): void
    {
        $repository = new DBBalanceRepository;
        $actual = $repository->transaction(function () {
            return true;
        });

        self::assertTrue($actual);
    }
}
