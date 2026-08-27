<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DadosUsuarioController;
use App\Http\Controllers\Api\V1\MovimentacaoController;
use App\Http\Controllers\Api\V1\MovimentacaoOpcoesController;
use App\Http\Controllers\Api\V1\FormaPagamentoController;
use App\Http\Controllers\Api\V1\PasswordController;
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

        Route::get('/movimentacoes/opcoes', [
            MovimentacaoOpcoesController::class,
            'index',
        ]);

        Route::get('/categorias', [
            CategoriaController::class,
            'index',
        ]);

        Route::post('/categorias', [
            CategoriaController::class,
            'store',
        ]);

        Route::put('/categorias/{id}', [
            CategoriaController::class,
            'update',
        ]);

        Route::delete('/categorias/{id}', [
            CategoriaController::class,
            'destroy',
        ]);

        Route::get('/formas-pagamento', [
            FormaPagamentoController::class,
            'index',
        ]);

        Route::post('/formas-pagamento', [
            FormaPagamentoController::class,
            'store',
        ]);

        Route::put('/formas-pagamento/{id}', [
            FormaPagamentoController::class,
            'update',
        ]);

        Route::delete('/formas-pagamento/{id}', [
            FormaPagamentoController::class,
            'destroy',
        ]);

        Route::get('/movimentacoes/{id}', [
            MovimentacaoController::class,
            'show',
        ]);

        Route::get('/dados-usuario', [
            DadosUsuarioController::class,
            'show',
        ]);

        Route::patch('/password', [
            PasswordController::class,
            'update',
        ]);

        Route::patch('/dados-usuario', [
            DadosUsuarioController::class,
            'update',
        ]);

        Route::put('/movimentacoes/{id}', [
            MovimentacaoController::class,
            'update',
        ]);

        Route::delete('/movimentacoes/{id}', [
            MovimentacaoController::class,
            'destroy',
        ]);

        Route::patch('/movimentacoes/{id}/pagar', [
            MovimentacaoController::class,
            'marcarComoPago',
        ]);

        Route::post('/movimentacoes', [
            MovimentacaoController::class,
            'store',
        ]);
    });
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:5,1');

        Route::post('/forgot-password', [AuthController::class,'forgotPassword',])
            ->middleware('throttle:5,1');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');

        Route::middleware([ 'auth:sanctum', 'api.active',])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
});
