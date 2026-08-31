<?php

use App\Models\Movimentacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('filtro de setembro funciona corretamente quando o dia atual e 31', function () {
    /** @var \Tests\TestCase $this */

    Carbon::setTestNow(
        Carbon::parse('2026-08-31 12:00:00')
    );

    $user = User::factory()->create();

    $this->actingAs($user);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Parcela de setembro',
        'valor' => 25,
        'data' => '2026-09-30',
        'categoria' => 'Carro',
        'forma_pagamento' => 'CARTÃO DE CRÉDITO',
        'status' => 'pendente',
    ]);

    /*
     * Esta movimentação não pode aparecer
     * quando setembro estiver selecionado.
     */
    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Parcela de outubro',
        'valor' => 25,
        'data' => '2026-10-31',
        'categoria' => 'Carro',
        'forma_pagamento' => 'CARTÃO DE CRÉDITO',
        'status' => 'pendente',
    ]);

    $response = $this->get(
        '/movimentacoes?mes=2026-09&tipo=todos&status=todos'
    );

    $response
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('movimentacoes/index')
                ->where('filtros.mes', '2026-09')
                ->where('resumo.despesas', 25)
                ->where('resumo.pendentes', 25)
                ->has('movimentacoes', 1)
                ->where(
                    'movimentacoes.0.descricao',
                    'Parcela de setembro'
                )
                ->where(
                    'movimentacoes.0.data',
                    '2026-09-30'
                )
        );
});
