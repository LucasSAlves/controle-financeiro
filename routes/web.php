<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FormaPagamentoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\RelatorioController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('relatorios', [RelatorioController::class, 'index'])
        ->name('relatorios.index');

    Route::get('/testes', function () {
        abort_unless(app()->environment('local'), 404);

        return Inertia::render('testes/index');
    })->name('testes.index');

    Route::patch('movimentacoes/{movimentacao}/marcar-como-pago', [MovimentacaoController::class, 'marcarComoPago'])
        ->name('movimentacoes.marcar-como-pago');

    Route::resource('movimentacoes', MovimentacaoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('categorias', CategoriaController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('formas-pagamento', FormaPagamentoController::class)
    ->only(['index', 'store', 'update','destroy']);
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('usuarios', [UserController::class, 'index'])
            ->name('users.index');

        Route::patch('usuarios/{user}/bloquear', [UserController::class, 'block'])
            ->name('users.block');

        Route::patch('usuarios/{user}/reativar', [UserController::class, 'activate'])
            ->name('users.activate');

        Route::patch('usuarios/{user}/promover', [UserController::class, 'promote'])
            ->name('users.promote');

        Route::patch('usuarios/{user}/remover-administrador', [UserController::class, 'demote'])
            ->name('users.demote');
    });
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
