<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\BalanceController;
use App\Http\Requests\SaveBalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Services\BalanceService;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class BalanceControllerTest extends TestCase
{
    public function testBalance(): void
    {
        $userID = 1;
        $price = 99.99;

        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('get')->with($userID)->andReturn($price);
        $controller = new BalanceController($service);
        $response = $controller->balance($userID);


        self::assertInstanceOf(BalanceResource::class, $response);
        self::assertEquals(['balance' => $price], $response->resource);
    }

    public function testSaveSuccess(): void
    {
        $userID = 2;
        $price = 100.01;
        $request = new SaveBalanceRequest(['balance' => $price]);

        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('save')->with($userID, $price)->andReturn(true);
        $controller = new BalanceController($service);
        $response = $controller->saveBalance($request, $userID);

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(\Symfony\Component\HttpFoundation\Response::HTTP_OK, $response->status());
    }

    public function testSaveFailed(): void
    {
        $userID = 2;
        $price = 100.01;
        $request = new SaveBalanceRequest(['balance' => $price]);

        $service = Mockery::mock(BalanceService::class);
        $service->shouldReceive('save')->with($userID, $price)->andReturn(false);
        $controller = new BalanceController($service);
        $response = $controller->saveBalance($request, $userID);

        self::assertInstanceOf(Response::class, $response);
        self::assertEquals(\Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, $response->status());
    }
}
