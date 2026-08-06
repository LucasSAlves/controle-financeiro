<?php

use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('movimentacoes rejeitam acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->getJson(
        '/api/v1/movimentacoes'
    );

    $response->assertUnauthorized();
});

test('usuario autenticado pode consultar as movimentacoes do mes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Salário',
        'valor' => 1000,
        'data' => '2026-08-10',
        'categoria' => 'Salário',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Conta de energia',
        'valor' => 200,
        'data' => '2026-08-15',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pago',
        'data_pagamento' => '2026-08-15',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Conta de água',
        'valor' => 300,
        'data' => '2026-08-20',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Boleto',
        'status' => 'pendente',
    ]);

    /*
     * Não pertence ao mês consultado.
     */
    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada de julho',
        'valor' => 5000,
        'data' => '2026-07-31',
        'status' => 'recebido',
    ]);

    $response = $this->getJson(
        '/api/v1/movimentacoes?mes=2026-08'
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'filtros.mes',
            '2026-08'
        )
        ->assertJsonPath(
            'filtros.tipo',
            'todos'
        )
        ->assertJsonPath(
            'filtros.status',
            'todos'
        )
        ->assertJsonPath(
            'resumo.entradas',
            1000
        )
        ->assertJsonPath(
            'resumo.despesas',
            500
        )
        ->assertJsonPath(
            'resumo.saldo',
            500
        )
        ->assertJsonPath(
            'resumo.pendentes',
            300
        )
        ->assertJsonCount(
            3,
            'movimentacoes'
        )
        ->assertJsonPath(
            'movimentacoes.0.descricao',
            'Conta de água'
        )
        ->assertJsonPath(
            'movimentacoes.1.descricao',
            'Conta de energia'
        )
        ->assertJsonPath(
            'movimentacoes.2.descricao',
            'Salário'
        )
        ->assertJsonStructure([
            'filtros' => [
                'mes',
                'tipo',
                'status',
            ],
            'resumo' => [
                'entradas',
                'despesas',
                'saldo',
                'pendentes',
            ],
            'movimentacoes' => [
                '*' => [
                    'id',
                    'tipo',
                    'descricao',
                    'valor',
                    'data',
                    'data_pagamento',
                    'categoria',
                    'forma_pagamento',
                    'status',
                    'observacao',
                    'parcelado',
                    'parcela_fixa',
                    'fixo_mensal',
                    'despesa_fixa_id',
                    'entrada_fixa_id',
                    'parcela_atual',
                    'total_parcelas',
                    'grupo_parcelamento',
                    'mes_atual',
                    'total_meses',
                    'grupo_fixo_mensal',
                ],
            ],
        ])
        ->assertJsonMissing([
            'descricao' => 'Entrada de julho',
        ]);
});

test('movimentacoes podem ser filtradas por tipo e status', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada recebida',
        'valor' => 1000,
        'data' => '2026-08-05',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa paga',
        'valor' => 200,
        'data' => '2026-08-10',
        'status' => 'pago',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa pendente',
        'valor' => 300,
        'data' => '2026-08-15',
        'status' => 'pendente',
    ]);

    $response = $this->getJson(
        '/api/v1/movimentacoes'
        . '?mes=2026-08'
        . '&tipo=despesa'
        . '&status=pendente'
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'filtros.tipo',
            'despesa'
        )
        ->assertJsonPath(
            'filtros.status',
            'pendente'
        )
        ->assertJsonCount(
            1,
            'movimentacoes'
        )
        ->assertJsonPath(
            'movimentacoes.0.descricao',
            'Despesa pendente'
        )
        ->assertJsonPath(
            'movimentacoes.0.tipo',
            'despesa'
        )
        ->assertJsonPath(
            'movimentacoes.0.status',
            'pendente'
        )
        ->assertJsonMissing([
            'descricao' => 'Entrada recebida',
        ])
        ->assertJsonMissing([
            'descricao' => 'Despesa paga',
        ]);
});

test('movimentacoes rejeitam filtros invalidos', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/movimentacoes'
        . '?mes=agosto-2026'
        . '&tipo=qualquer'
        . '&status=atrasado'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'mes',
            'tipo',
            'status',
        ]);
});

test('movimentacoes exibem somente dados do usuario autenticado', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada do usuário',
        'valor' => 800,
        'data' => '2026-08-05',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada de outro usuário',
        'valor' => 5000,
        'data' => '2026-08-05',
        'status' => 'recebido',
    ]);

    $response = $this->getJson(
        '/api/v1/movimentacoes?mes=2026-08'
    );

    $response
        ->assertOk()
        ->assertJsonCount(
            1,
            'movimentacoes'
        )
        ->assertJsonPath(
            'resumo.entradas',
            800
        )
        ->assertJsonPath(
            'movimentacoes.0.descricao',
            'Entrada do usuário'
        )
        ->assertJsonMissing([
            'descricao' => 'Entrada de outro usuário',
        ]);
});

test('usuario bloqueado nao pode consultar as movimentacoes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/movimentacoes'
    );

    $response
        ->assertForbidden()
        ->assertJson([
            'message' =>
                'Esta conta está bloqueada. Entre em contato com o administrador.',

            'code' => 'ACCOUNT_BLOCKED',
        ]);
});
