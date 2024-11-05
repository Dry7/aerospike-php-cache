<?php

namespace Tests\Unit\Http\Resources;

use App\Contracts\BalanceRepository;
use App\Contracts\CacheRepository;
use App\Contracts\Locker;
use App\Http\Resources\BalanceResource;
use App\Repositories\NullBalanceRepository;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class BalanceResourceTest extends TestCase
{
    public function test(): void
    {
        $balance = 99.99;

        $request = new Request();
        $resource = new BalanceResource(['balance' => $balance]);
        $actual = $resource->toArray($request);

        self::assertEquals(['balance' => $balance], $actual);
    }

    public function testEmpty(): void
    {
        $balance = null;

        $request = new Request();
        $resource = new BalanceResource(['balance' => $balance]);
        $actual = $resource->toArray($request);

        self::assertEquals(['balance' => 0.00], $actual);
    }
}
