<?php

use App\Models\Categoria;
use App\Models\DespesaFixa;
use App\Models\EntradaFixa;
use App\Models\FormaPagamento;
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

test('cadastro de movimentacao rejeita acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->postJson(
        '/api/v1/movimentacoes',
        [
            'tipo' => 'entrada',
            'descricao' => 'Entrada teste',
            'valor' => 1000,
            'data' => '2026-08-10',
            'status' => 'recebido',
        ]
    );

    $response->assertUnauthorized();
});

test('usuario autenticado pode cadastrar entrada normal', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/movimentacoes',
        [
            'tipo' => 'entrada',
            'descricao' => 'Salário aplicativo',
            'valor' => 1500,
            'data' => '2026-08-10',
            'categoria' => 'Salário',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => 'Entrada cadastrada pelo app',
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Movimentação cadastrada com sucesso.'
        )
        ->assertJsonPath(
            'movimentacao.tipo',
            'entrada'
        )
        ->assertJsonPath(
            'movimentacao.status',
            'recebido'
        )
        ->assertJsonPath(
            'movimentacao.valor',
            1500
        )
        ->assertJsonPath(
            'movimentacao.forma_pagamento',
            null
        );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'user_id' => $user->id,
            'tipo' => 'entrada',
            'descricao' => 'Salário aplicativo',
            'valor' => 1500,
            'status' => 'recebido',
            'forma_pagamento' => null,
            'parcelado' => false,
            'parcela_fixa' => false,
            'fixo_mensal' => false,
        ]
    );

    $movimentacao = Movimentacao::where(
        'user_id',
        $user->id
    )
        ->where(
            'descricao',
            'Salário aplicativo'
        )
        ->firstOrFail();

    expect(
        $movimentacao->data->format('Y-m-d')
    )->toBe('2026-08-10');
});

test('despesa normal paga recebe data de pagamento', function () {
    /** @var \Tests\TestCase $this */

    \Carbon\Carbon::setTestNow(
        '2026-08-09 10:00:00'
    );

    try {
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
            ['mobile']
        );

        $response = $this->postJson(
            '/api/v1/movimentacoes',
            [
                'tipo' => 'despesa',
                'descricao' => 'Conta paga pelo app',
                'valor' => 250,
                'data' => '2026-08-15',
                'categoria' => 'Casa',
                'forma_pagamento' => 'Pix',
                'status' => 'pago',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'movimentacao.status',
                'pago'
            )
            ->assertJsonPath(
                'movimentacao.data_pagamento',
                '2026-08-09'
            );

        $this->assertDatabaseHas(
            'movimentacoes',
            [
                'user_id' => $user->id,
                'descricao' => 'Conta paga pelo app',
                'tipo' => 'despesa',
                'valor' => 250,
                'status' => 'pago',
            ]
        );

        $movimentacao = Movimentacao::where(
            'user_id',
            $user->id
        )
            ->where(
                'descricao',
                'Conta paga pelo app'
            )
            ->firstOrFail();

        expect(
            $movimentacao->data_pagamento
                ?->format('Y-m-d')
        )->toBe('2026-08-09');
    } finally {
        \Carbon\Carbon::setTestNow();
    }
});

test('usuario pode cadastrar despesa parcelada pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/movimentacoes',
        [
            'tipo' => 'despesa',
            'descricao' => 'Notebook',
            'valor' => 300,
            'data' => '2026-08-10',
            'categoria' => 'Compras',
            'forma_pagamento' => 'Cartão',
            'status' => 'pendente',
            'parcelado' => true,
            'total_parcelas' => 3,
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Compra parcelada cadastrada com sucesso.'
        )
        ->assertJsonCount(
            3,
            'parcelas'
        )
        ->assertJsonPath(
            'parcelas.0.descricao',
            'Notebook - Parcela 1/3'
        )
        ->assertJsonPath(
            'parcelas.0.valor',
            300
        )
        ->assertJsonPath(
            'parcelas.0.data',
            '2026-08-10'
        )
        ->assertJsonPath(
            'parcelas.1.data',
            '2026-09-10'
        )
        ->assertJsonPath(
            'parcelas.2.data',
            '2026-10-10'
        );

    $this->assertDatabaseCount(
        'movimentacoes',
        3
    );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'user_id' => $user->id,
            'descricao' => 'Notebook - Parcela 1/3',
            'valor' => 300,
            'parcela_atual' => 1,
            'total_parcelas' => 3,
            'parcelado' => true,
            'status' => 'pendente',
        ]
    );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'user_id' => $user->id,
            'descricao' => 'Notebook - Parcela 3/3',
            'valor' => 300,
            'parcela_atual' => 3,
            'total_parcelas' => 3,
        ]
    );

    $terceiraParcela = Movimentacao::where(
        'user_id',
        $user->id
    )
        ->where(
            'descricao',
            'Notebook - Parcela 3/3'
        )
        ->firstOrFail();

    expect(
        $terceiraParcela->data->format('Y-m-d')
    )->toBe('2026-10-10');
});


test('usuario pode cadastrar entrada fixa mensal pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/movimentacoes',
        [
            'tipo' => 'entrada',
            'descricao' => 'Salário fixo',
            'valor' => 2000,
            'data' => '2026-08-05',
            'categoria' => 'Salário',
            'status' => 'recebido',
            'fixo_mensal' => true,
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Entrada fixa mensal cadastrada com sucesso.'
        );

    $entradaFixa = EntradaFixa::where(
        'user_id',
        $user->id
    )
        ->where(
            'descricao',
            'Salário fixo'
        )
        ->firstOrFail();

    expect(
        (float) $entradaFixa->valor
    )->toBe(2000.0);

    expect(
        \Carbon\Carbon::parse(
            $entradaFixa->data_inicio
        )->format('Y-m-d')
    )->toBe('2026-08-05');

    $movimentacao = Movimentacao::where(
        'user_id',
        $user->id
    )
        ->where(
            'entrada_fixa_id',
            $entradaFixa->id
        )
        ->firstOrFail();

    expect($movimentacao->tipo)
        ->toBe('entrada');

    expect($movimentacao->status)
        ->toBe('recebido');

    expect($movimentacao->fixo_mensal)
        ->toBeTrue();

    expect(
        $movimentacao->data->format('Y-m-d')
    )->toBe('2026-08-05');
});

test('usuario pode cadastrar despesa fixa mensal pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/movimentacoes',
        [
            'tipo' => 'despesa',
            'descricao' => 'Aluguel fixo',
            'valor' => 900,
            'data' => '2026-08-12',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'parcela_fixa' => true,
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Despesa fixa mensal cadastrada com sucesso.'
        );

    $despesaFixa = DespesaFixa::where(
        'user_id',
        $user->id
    )
        ->where(
            'descricao',
            'Aluguel fixo'
        )
        ->firstOrFail();

    expect(
        (float) $despesaFixa->valor
    )->toBe(900.0);

    expect(
        \Carbon\Carbon::parse(
            $despesaFixa->data_inicio
        )->format('Y-m-d')
    )->toBe('2026-08-12');

    $movimentacao = Movimentacao::where(
        'user_id',
        $user->id
    )
        ->where(
            'despesa_fixa_id',
            $despesaFixa->id
        )
        ->firstOrFail();

    expect($movimentacao->tipo)
        ->toBe('despesa');

    expect($movimentacao->status)
        ->toBe('pendente');

    expect($movimentacao->parcela_fixa)
        ->toBeTrue();

    expect(
        $movimentacao->data->format('Y-m-d')
    )->toBe('2026-08-12');
});

test('opcoes de movimentacao rejeitam acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->getJson(
        '/api/v1/movimentacoes/opcoes?tipo=despesa'
    );

    $response->assertUnauthorized();
});

test('opcoes de despesa retornam somente dados ativos do usuario autenticado', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Casa',
        'ativo' => true,
    ]);

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Desativada',
        'ativo' => false,
    ]);

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'nome' => 'Salário',
        'ativo' => true,
    ]);

    Categoria::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'nome' => 'Categoria outro usuário',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Cartão inativo',
        'ativo' => false,
    ]);

    FormaPagamento::create([
        'user_id' => $outroUsuario->id,
        'nome' => 'Forma outro usuário',
        'ativo' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/movimentacoes/opcoes?tipo=despesa'
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'tipo',
            'despesa'
        )
        ->assertJsonCount(
            1,
            'categorias'
        )
        ->assertJsonPath(
            'categorias.0.nome',
            'Casa'
        )
        ->assertJsonCount(
            1,
            'formas_pagamento'
        )
        ->assertJsonPath(
            'formas_pagamento.0.nome',
            'Pix'
        )
        ->assertJsonMissing([
            'nome' => 'Desativada',
        ])
        ->assertJsonMissing([
            'nome' => 'Salário',
        ])
        ->assertJsonMissing([
            'nome' => 'Categoria outro usuário',
        ])
        ->assertJsonMissing([
            'nome' => 'Cartão inativo',
        ])
        ->assertJsonMissing([
            'nome' => 'Forma outro usuário',
        ]);
});

test('opcoes de entrada retornam categorias de entrada e nenhuma forma de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'nome' => 'Salário',
        'ativo' => true,
    ]);

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Casa',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/movimentacoes/opcoes?tipo=entrada'
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'tipo',
            'entrada'
        )
        ->assertJsonCount(
            1,
            'categorias'
        )
        ->assertJsonPath(
            'categorias.0.nome',
            'Salário'
        )
        ->assertJsonCount(
            0,
            'formas_pagamento'
        )
        ->assertJsonMissing([
            'nome' => 'Casa',
        ])
        ->assertJsonMissing([
            'nome' => 'Pix',
        ]);
});

test('opcoes de movimentacao rejeitam tipo invalido', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/movimentacoes/opcoes?tipo=qualquer'
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'tipo',
        ]);
});
