<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MovimentacaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FormaPagamentoController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::patch('movimentacoes/{movimentacao}/marcar-como-pago', [MovimentacaoController::class, 'marcarComoPago'])
        ->name('movimentacoes.marcar-como-pago');

    Route::resource('movimentacoes', MovimentacaoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('categorias', CategoriaController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('formas-pagamento', FormaPagamentoController::class)
    ->only(['index', 'store', 'update','destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
