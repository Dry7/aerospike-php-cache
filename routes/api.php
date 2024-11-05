<?php

use App\Http\Controllers\BalanceController;
use Illuminate\Support\Facades\Route;

Route::controller(BalanceController::class)->group(function () {
    Route::get('/user/{id}/balance', 'balance')->name('balance.get');
    Route::post('/user/{id}/balance/update', 'saveBalance')->name('balance.save');
});
