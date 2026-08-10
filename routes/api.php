<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MovimentacaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware([
        'auth:sanctum',
        'api.active',
    ])->group(function () {
        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ]);

        Route::get('/movimentacoes', [
            MovimentacaoController::class,
            'index',
        ]);

        Route::post('/movimentacoes', [
            MovimentacaoController::class,
            'store',
        ]);
    });
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');

        Route::middleware([
            'auth:sanctum',
            'api.active',
        ])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
});
