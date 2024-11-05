<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Services\BalanceService;
use Symfony\Component\HttpFoundation\Response;

class BalanceController
{
    public function __construct(private readonly BalanceService $service) {}

    public function balance($userID): BalanceResource
    {
        return new BalanceResource([
            'balance' => $this->service->get($userID),
        ]);
    }

    public function saveBalance(SaveBalanceRequest $request, int $userID)
    {
        $status = $this->service->save($userID, $request->input('balance'))
            ? Response::HTTP_OK
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        return response(null, $status);
    }
}
