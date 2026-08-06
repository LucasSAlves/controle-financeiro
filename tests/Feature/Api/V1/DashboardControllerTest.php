<?php

use App\Models\Movimentacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
uses(RefreshDatabase::class);

test('dashboard rejeita acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->getJson('/api/v1/dashboard');

    $response->assertUnauthorized();
});

test('usuario autenticado pode consultar o dashboard', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson('/api/v1/dashboard');

    $response
        ->assertOk()
        ->assertJson([
            'filtros' => [
                'mes' => now()->format('Y-m'),
            ],
            'resumo' => [
                'saldo_anterior' => 0,
                'entradas' => 0,
                'despesas' => 0,
                'total_disponivel' => 0,
                'saldo' => 0,
            ],
        ]);
});

test('dashboard calcula o saldo acumulado do mes selecionado', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada anterior',
        'valor' => 1000,
        'data' => '2026-07-10',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa anterior',
        'valor' => 500,
        'data' => '2026-07-15',
        'status' => 'pago',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada de agosto',
        'valor' => 1000,
        'data' => '2026-08-05',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa de agosto',
        'valor' => 300,
        'data' => '2026-08-10',
        'status' => 'pendente',
    ]);

    $response = $this->getJson(
        '/api/v1/dashboard?mes=2026-08'
    );

    $response
        ->assertOk()
        ->assertJsonPath('filtros.mes', '2026-08')
        ->assertJsonPath('resumo.saldo_anterior', 500)
        ->assertJsonPath('resumo.entradas', 1000)
        ->assertJsonPath('resumo.despesas', 300)
        ->assertJsonPath('resumo.total_disponivel', 1500)
        ->assertJsonPath('resumo.saldo', 1200);
});

test('dashboard rejeita mes em formato invalido', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/dashboard?mes=agosto-2026'
    );

    $response
        ->assertStatus(422)
        ->assertJson([
            'message' => 'O mês informado é inválido.',
            'errors' => [
                'mes' => [
                    'Informe o mês no formato AAAA-MM.',
                ],
            ],
        ]);
});

test('usuario bloqueado nao pode consultar o dashboard', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson('/api/v1/dashboard');

    $response
        ->assertForbidden()
        ->assertJson([
            'message' => 'Esta conta está bloqueada. Entre em contato com o administrador.',
            'code' => 'ACCOUNT_BLOCKED',
        ]);
});

test('dashboard considera somente movimentacoes do usuario autenticado', function () {
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
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa do usuário',
        'valor' => 200,
        'data' => '2026-08-10',
        'status' => 'pago',
    ]);

    Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada de outro usuário',
        'valor' => 5000,
        'data' => '2026-08-05',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa de outro usuário',
        'valor' => 3000,
        'data' => '2026-08-10',
        'status' => 'pago',
    ]);

    $response = $this->getJson(
        '/api/v1/dashboard?mes=2026-08'
    );

    $response
        ->assertOk()
        ->assertJsonPath('resumo.saldo_anterior', 0)
        ->assertJsonPath('resumo.entradas', 800)
        ->assertJsonPath('resumo.despesas', 200)
        ->assertJsonPath('resumo.total_disponivel', 800)
        ->assertJsonPath('resumo.saldo', 600);
});

test('dashboard retorna as cinco ultimas movimentacoes do usuario e do mes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    for ($dia = 1; $dia <= 7; $dia++) {
        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => $dia % 2 === 0
                ? 'despesa'
                : 'entrada',
            'descricao' => "Movimentação {$dia}",
            'valor' => $dia * 100,
            'data' => sprintf(
                '2026-08-%02d',
                $dia
            ),
            'status' => $dia % 2 === 0
                ? 'pendente'
                : 'recebido',
            'categoria' => 'Teste',
            'forma_pagamento' => 'Pix',
        ]);
    }

    Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Movimentação de julho',
        'valor' => 900,
        'data' => '2026-07-31',
        'status' => 'recebido',
    ]);

    Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'entrada',
        'descricao' => 'Movimentação de outro usuário',
        'valor' => 5000,
        'data' => '2026-08-31',
        'status' => 'recebido',
    ]);

    $response = $this->getJson(
        '/api/v1/dashboard?mes=2026-08'
    );

    $response
        ->assertOk()
        ->assertJsonCount(
            5,
            'ultimas_movimentacoes'
        )
        ->assertJsonPath(
            'ultimas_movimentacoes.0.descricao',
            'Movimentação 7'
        )
        ->assertJsonPath(
            'ultimas_movimentacoes.1.descricao',
            'Movimentação 6'
        )
        ->assertJsonPath(
            'ultimas_movimentacoes.2.descricao',
            'Movimentação 5'
        )
        ->assertJsonPath(
            'ultimas_movimentacoes.3.descricao',
            'Movimentação 4'
        )
        ->assertJsonPath(
            'ultimas_movimentacoes.4.descricao',
            'Movimentação 3'
        )
        ->assertJsonStructure([
            'ultimas_movimentacoes' => [
                '*' => [
                    'id',
                    'tipo',
                    'descricao',
                    'valor',
                    'data',
                    'categoria',
                    'forma_pagamento',
                    'status',
                ],
            ],
               ])
                ->assertJsonMissing([
                    'descricao' => 'Movimentação de julho',
                ])
                ->assertJsonMissing([
                    'descricao' =>
                        'Movimentação de outro usuário',
                ]);
});

test('dashboard separa corretamente os avisos de vencimento', function () {
    /** @var \Tests\TestCase $this */

    Carbon::setTestNow('2026-08-06 10:00:00');

    try {
        $user = User::factory()->create();
        $outroUsuario = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['mobile']
        );

        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa vencida',
            'valor' => 100,
            'data' => '2026-08-05',
            'status' => 'pendente',
        ]);

        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa vence hoje',
            'valor' => 200,
            'data' => '2026-08-06',
            'status' => 'pendente',
        ]);

        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa próximos dias',
            'valor' => 300,
            'data' => '2026-08-13',
            'status' => 'pendente',
        ]);

        /*
         * Está depois do limite de sete dias
         * e não deve entrar em próximos dias.
         */
        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa fora do período',
            'valor' => 400,
            'data' => '2026-08-14',
            'status' => 'pendente',
        ]);

        /*
         * Está vencida, mas já foi paga.
         */
        Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa vencida paga',
            'valor' => 500,
            'data' => '2026-08-04',
            'status' => 'pago',
        ]);

        /*
         * Pertence a outro usuário.
         */
        Movimentacao::create([
            'user_id' => $outroUsuario->id,
            'tipo' => 'despesa',
            'descricao' => 'Despesa de outro usuário',
            'valor' => 600,
            'data' => '2026-08-05',
            'status' => 'pendente',
        ]);

        $response = $this->getJson(
            '/api/v1/dashboard?mes=2026-08'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'avisos_vencimento.vencidas'
            )
            ->assertJsonCount(
                1,
                'avisos_vencimento.vencem_hoje'
            )
            ->assertJsonCount(
                1,
                'avisos_vencimento.proximos_dias'
            )
            ->assertJsonPath(
                'avisos_vencimento.vencidas.0.descricao',
                'Despesa vencida'
            )
            ->assertJsonPath(
                'avisos_vencimento.vencem_hoje.0.descricao',
                'Despesa vence hoje'
            )
            ->assertJsonPath(
                'avisos_vencimento.proximos_dias.0.descricao',
                'Despesa próximos dias'
            );
    } finally {
        Carbon::setTestNow();
    }
});
