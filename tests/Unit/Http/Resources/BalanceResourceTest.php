<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\BalanceResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class BalanceResourceTest extends TestCase
{
    public function test(): void
    {
        $balance = 99.99;

        $request = new Request;
        $resource = new BalanceResource(['balance' => $balance]);
        $actual = $resource->toArray($request);

        self::assertEquals(['balance' => $balance], $actual);
    }

    public function testEmpty(): void
    {
        $balance = null;

        $request = new Request;
        $resource = new BalanceResource(['balance' => $balance]);
        $actual = $resource->toArray($request);

        self::assertEquals(['balance' => 0.00], $actual);
    }
}
