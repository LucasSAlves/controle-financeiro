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

test('usuario autenticado pode consultar uma movimentacao propria', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Conta de energia',
        'valor' => 150,
        'data' => '2026-08-13',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => 'Teste detalhes',
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->getJson(
        "/api/v1/movimentacoes/{$movimentacao->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'movimentacao.id',
            $movimentacao->id
        )
        ->assertJsonPath(
            'movimentacao.tipo',
            'despesa'
        )
        ->assertJsonPath(
            'movimentacao.descricao',
            'Conta de energia'
        )
        ->assertJsonPath(
            'movimentacao.valor',
            150
        )
        ->assertJsonPath(
            'movimentacao.categoria',
            'Casa'
        )
        ->assertJsonPath(
            'movimentacao.forma_pagamento',
            'Pix'
        )
        ->assertJsonPath(
            'movimentacao.status',
            'pendente'
        );
});

test('usuario nao pode consultar movimentacao de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa de outro usuario',
        'valor' => 200,
        'data' => '2026-08-13',
        'categoria' => null,
        'forma_pagamento' => null,
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->getJson(
        "/api/v1/movimentacoes/{$movimentacao->id}"
    );

    $response->assertNotFound();
});

test('usuario pode marcar despesa propria como paga', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Internet',
        'valor' => 100,
        'data' => '2026-08-13',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/movimentacoes/{$movimentacao->id}/pagar"
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Despesa marcada como paga com sucesso.'
        )
        ->assertJsonPath(
            'movimentacao.status',
            'pago'
        );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $movimentacao->id,
            'user_id' => $user->id,
            'status' => 'pago',
        ]
    );

    $movimentacao->refresh();

    expect(
        $movimentacao->data_pagamento
            ? $movimentacao->data_pagamento->toDateString()
            : null
    )->toBe(
        now()->toDateString()
    );
});

test('entrada nao pode ser marcada como paga', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Salario',
        'valor' => 1000,
        'data' => '2026-08-13',
        'categoria' => 'Salario',
        'forma_pagamento' => null,
        'status' => 'recebido',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/movimentacoes/{$movimentacao->id}/pagar"
    );

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'message',
            'Somente despesas podem ser marcadas como pagas.'
        );
});

test('usuario nao pode marcar despesa de outro usuario como paga', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'descricao' => 'Despesa protegida',
        'valor' => 50,
        'data' => '2026-08-13',
        'categoria' => null,
        'forma_pagamento' => null,
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/movimentacoes/{$movimentacao->id}/pagar"
    );

    $response->assertNotFound();

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $movimentacao->id,
            'status' => 'pendente',
            'data_pagamento' => null,
        ]
    );
});

test('usuario pode editar despesa normal propria pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Conta antiga',
        'valor' => 100,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$movimentacao->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Conta atualizada',
            'valor' => 150,
            'data' => '2026-08-20',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Cartao',
            'status' => 'pago',
            'observacao' => 'Editada pelo app',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Movimentacao atualizada com sucesso.'
        )
        ->assertJsonPath(
            'movimentacao.descricao',
            'Conta atualizada'
        )
        ->assertJsonPath(
            'movimentacao.status',
            'pago'
        );

    $movimentacao->refresh();

    expect($movimentacao->descricao)
        ->toBe('Conta atualizada');

    expect((float) $movimentacao->valor)
        ->toBe(150.0);

    expect($movimentacao->data->toDateString())
        ->toBe('2026-08-20');

    expect($movimentacao->forma_pagamento)
        ->toBe('Cartao');

    expect($movimentacao->status)
        ->toBe('pago');

    expect(
        $movimentacao->data_pagamento
            ? $movimentacao->data_pagamento
                ->toDateString()
            : null
    )->toBe(
        now()->toDateString()
    );
});

test('edicao de entrada normal mantem status recebido e sem forma de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'descricao' => 'Entrada antiga',
        'valor' => 500,
        'data' => '2026-08-10',
        'categoria' => 'Salario',
        'forma_pagamento' => null,
        'status' => 'recebido',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$movimentacao->id}",
        [
            'tipo' => 'entrada',
            'descricao' => 'Entrada atualizada',
            'valor' => 600,
            'data' => '2026-08-21',
            'categoria' => 'Salario',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => 'Teste entrada',
        ]
    );

    $response->assertOk();

    $movimentacao->refresh();

    expect($movimentacao->status)
        ->toBe('recebido');

    expect($movimentacao->forma_pagamento)
        ->toBeNull();

    expect($movimentacao->data_pagamento)
        ->toBeNull();
});

test('usuario nao pode editar movimentacao normal de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'descricao' => 'Movimentacao protegida',
        'valor' => 100,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$movimentacao->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Tentativa',
            'valor' => 200,
            'data' => '2026-08-20',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => null,
        ]
    );

    $response->assertNotFound();

    $movimentacao->refresh();

    expect($movimentacao->descricao)
        ->toBe('Movimentacao protegida');
});

test('usuario pode excluir movimentacao normal propria pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Excluir pelo app',
        'valor' => 50,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$movimentacao->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Movimentacao excluida com sucesso.'
        );

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $movimentacao->id,
            'user_id' => $user->id,
        ]
    );
});

test('usuario nao pode excluir movimentacao normal de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $movimentacao = \App\Models\Movimentacao::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'descricao' => 'Nao excluir',
        'valor' => 50,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'data_pagamento' => null,
    ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$movimentacao->id}"
    );

    $response->assertNotFound();

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $movimentacao->id,
            'user_id' => $outroUsuario->id,
        ]
    );
});

test('usuario pode editar parcela e aumentar quantidade total pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-parcelado-aumentar';

    $parcela1 = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Notebook - Parcela 1/3',
        'valor' => 100,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Cartao',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => true,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'parcela_atual' => 1,
        'total_parcelas' => 3,
        'grupo_parcelamento' => $grupo,
        'data_pagamento' => null,
    ]);

    $parcela2 = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Notebook - Parcela 2/3',
        'valor' => 100,
        'data' => '2026-09-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Cartao',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => true,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'parcela_atual' => 2,
        'total_parcelas' => 3,
        'grupo_parcelamento' => $grupo,
        'data_pagamento' => null,
    ]);

    $parcela3 = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Notebook - Parcela 3/3',
        'valor' => 100,
        'data' => '2026-10-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Cartao',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => true,
        'parcela_fixa' => false,
        'fixo_mensal' => false,
        'parcela_atual' => 3,
        'total_parcelas' => 3,
        'grupo_parcelamento' => $grupo,
        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$parcela2->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Notebook atualizado - Parcela 2/3',
            'valor' => 120,
            'data' => '2026-09-15',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => 'Editado pelo app',
            'total_parcelas' => 4,
        ]
    );

    $response->assertOk();

    expect(
        \App\Models\Movimentacao::where(
            'grupo_parcelamento',
            $grupo
        )->count()
    )->toBe(4);

    $parcela2->refresh();

    expect((float) $parcela2->valor)
        ->toBe(120.0);

    expect($parcela2->data->toDateString())
        ->toBe('2026-09-15');

    expect($parcela2->forma_pagamento)
        ->toBe('Pix');

    expect($parcela2->descricao)
        ->toBe('Notebook atualizado - Parcela 2/4');

    $parcela1->refresh();
    $parcela3->refresh();

    expect((float) $parcela1->valor)
        ->toBe(100.0);

    expect((float) $parcela3->valor)
        ->toBe(100.0);

    expect($parcela1->total_parcelas)
        ->toBe(4);

    expect($parcela3->total_parcelas)
        ->toBe(4);

    $parcela4 = \App\Models\Movimentacao::where(
        'grupo_parcelamento',
        $grupo
    )
        ->where('parcela_atual', 4)
        ->firstOrFail();

    expect((float) $parcela4->valor)
        ->toBe(120.0);

    expect($parcela4->data->toDateString())
        ->toBe('2026-11-10');
});

test('usuario pode reduzir parcelamento removendo somente parcelas excedentes pendentes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-parcelado-reduzir';

    $parcelas = collect();

    foreach (range(1, 4) as $numero) {
        $parcelas->push(
            \App\Models\Movimentacao::create([
                'user_id' => $user->id,
                'tipo' => 'despesa',
                'descricao' =>
                    "Compra - Parcela {$numero}/4",
                'valor' => 50,
                'data' => \Carbon\Carbon::create(
                    2026,
                    8,
                    10
                )
                    ->addMonthsNoOverflow($numero - 1)
                    ->format('Y-m-d'),
                'categoria' => 'Casa',
                'forma_pagamento' => 'Pix',
                'status' => 'pendente',
                'observacao' => null,
                'parcelado' => true,
                'parcela_fixa' => false,
                'fixo_mensal' => false,
                'parcela_atual' => $numero,
                'total_parcelas' => 4,
                'grupo_parcelamento' => $grupo,
                'data_pagamento' => null,
            ])
        );
    }

    $parcela2 = $parcelas->get(1);
    $parcela4 = $parcelas->get(3);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$parcela2->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Compra - Parcela 2/4',
            'valor' => 50,
            'data' => '2026-09-10',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => null,
            'total_parcelas' => 3,
        ]
    );

    $response->assertOk();

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $parcela4->id,
        ]
    );

    expect(
        \App\Models\Movimentacao::where(
            'grupo_parcelamento',
            $grupo
        )->count()
    )->toBe(3);

    expect(
        \App\Models\Movimentacao::where(
            'grupo_parcelamento',
            $grupo
        )
            ->where('total_parcelas', 3)
            ->count()
    )->toBe(3);
});

test('api nao permite reduzir parcelamento removendo parcela ja paga', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-parcelado-paga';

    $parcela2 = null;
    $parcela4 = null;

    foreach (range(1, 4) as $numero) {
        $movimentacao =
            \App\Models\Movimentacao::create([
                'user_id' => $user->id,
                'tipo' => 'despesa',
                'descricao' =>
                    "Compra paga - Parcela {$numero}/4",
                'valor' => 80,
                'data' => \Carbon\Carbon::create(
                    2026,
                    8,
                    10
                )
                    ->addMonthsNoOverflow($numero - 1)
                    ->format('Y-m-d'),
                'categoria' => 'Casa',
                'forma_pagamento' => 'Pix',
                'status' =>
                    $numero === 4
                        ? 'pago'
                        : 'pendente',
                'observacao' => null,
                'parcelado' => true,
                'parcela_fixa' => false,
                'fixo_mensal' => false,
                'parcela_atual' => $numero,
                'total_parcelas' => 4,
                'grupo_parcelamento' => $grupo,
                'data_pagamento' =>
                    $numero === 4
                        ? '2026-11-10'
                        : null,
            ]);

        if ($numero === 2) {
            $parcela2 = $movimentacao;
        }

        if ($numero === 4) {
            $parcela4 = $movimentacao;
        }
    }

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$parcela2->id}",
        [
            'tipo' => 'despesa',
            'descricao' =>
                'Compra paga - Parcela 2/4',
            'valor' => 80,
            'data' => '2026-09-10',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pendente',
            'observacao' => null,
            'total_parcelas' => 3,
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $parcela4->id,
            'status' => 'pago',
        ]
    );

    expect(
        \App\Models\Movimentacao::where(
            'grupo_parcelamento',
            $grupo
        )->count()
    )->toBe(4);
});

test('usuario pode excluir somente parcela atual pendente pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-excluir-atual';

    $parcelas = collect();

    foreach (range(1, 3) as $numero) {
        $parcelas->push(
            \App\Models\Movimentacao::create([
                'user_id' => $user->id,
                'tipo' => 'despesa',
                'descricao' =>
                    "Excluir - Parcela {$numero}/3",
                'valor' => 25,
                'data' => \Carbon\Carbon::create(
                    2026,
                    8,
                    10
                )
                    ->addMonthsNoOverflow($numero - 1)
                    ->format('Y-m-d'),
                'categoria' => 'Casa',
                'forma_pagamento' => 'Pix',
                'status' => 'pendente',
                'observacao' => null,
                'parcelado' => true,
                'parcela_fixa' => false,
                'fixo_mensal' => false,
                'parcela_atual' => $numero,
                'total_parcelas' => 3,
                'grupo_parcelamento' => $grupo,
                'data_pagamento' => null,
            ])
        );
    }

    $parcela2 = $parcelas->get(1);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$parcela2->id}",
        [
            'modo_exclusao' => 'atual',
        ]
    );

    $response->assertOk();

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $parcela2->id,
        ]
    );

    expect(
        \App\Models\Movimentacao::where(
            'grupo_parcelamento',
            $grupo
        )->count()
    )->toBe(2);
});

test('usuario pode excluir parcela atual e futuras pendentes preservando pagas', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-excluir-futuras';

    $parcelas = collect();

    foreach (range(1, 4) as $numero) {
        $parcelas->push(
            \App\Models\Movimentacao::create([
                'user_id' => $user->id,
                'tipo' => 'despesa',
                'descricao' =>
                    "Excluir futuras - Parcela {$numero}/4",
                'valor' => 30,
                'data' => \Carbon\Carbon::create(
                    2026,
                    8,
                    10
                )
                    ->addMonthsNoOverflow($numero - 1)
                    ->format('Y-m-d'),
                'categoria' => 'Casa',
                'forma_pagamento' => 'Pix',
                'status' =>
                    $numero === 3
                        ? 'pago'
                        : 'pendente',
                'observacao' => null,
                'parcelado' => true,
                'parcela_fixa' => false,
                'fixo_mensal' => false,
                'parcela_atual' => $numero,
                'total_parcelas' => 4,
                'grupo_parcelamento' => $grupo,
                'data_pagamento' =>
                    $numero === 3
                        ? '2026-10-10'
                        : null,
            ])
        );
    }

    $parcela2 = $parcelas->get(1);
    $parcela3 = $parcelas->get(2);
    $parcela4 = $parcelas->get(3);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$parcela2->id}",
        [
            'modo_exclusao' => 'futuras',
        ]
    );

    $response->assertOk();

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $parcela2->id,
        ]
    );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $parcela3->id,
            'status' => 'pago',
        ]
    );

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $parcela4->id,
        ]
    );
});

test('api nao permite excluir parcela atual ja paga', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $movimentacao =
        \App\Models\Movimentacao::create([
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'descricao' => 'Parcela paga - Parcela 1/2',
            'valor' => 40,
            'data' => '2026-08-10',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Pix',
            'status' => 'pago',
            'observacao' => null,
            'parcelado' => true,
            'parcela_fixa' => false,
            'fixo_mensal' => false,
            'parcela_atual' => 1,
            'total_parcelas' => 2,
            'grupo_parcelamento' =>
                'grupo-parcela-paga',
            'data_pagamento' => '2026-08-10',
        ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$movimentacao->id}",
        [
            'modo_exclusao' => 'atual',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $movimentacao->id,
            'status' => 'pago',
        ]
    );
});

test('usuario pode editar somente o mes atual de despesa fixa pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-despesa-fixa-atual';

    $regra = \App\Models\DespesaFixa::create([
        'user_id' => $user->id,
        'grupo_recorrencia' => $grupo,
        'descricao' => 'Internet',
        'valor' => 100,
        'data_inicio' => '2026-08-10',
        'dia_vencimento' => 10,
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'observacao' => null,
        'ativa' => true,
        'encerrada_em' => null,
    ]);

    $agosto = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Internet',
        'valor' => 100,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,

        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,

        'despesa_fixa_id' => $regra->id,
        'entrada_fixa_id' => null,

        'parcela_atual' => null,
        'total_parcelas' => null,
        'grupo_parcelamento' => null,

        'mes_atual' => null,
        'total_meses' => null,
        'grupo_fixo_mensal' => null,

        'data_pagamento' => null,
    ]);

    $setembro = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Internet',
        'valor' => 100,
        'data' => '2026-09-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,

        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,

        'despesa_fixa_id' => $regra->id,
        'entrada_fixa_id' => null,

        'parcela_atual' => null,
        'total_parcelas' => null,
        'grupo_parcelamento' => null,

        'mes_atual' => null,
        'total_meses' => null,
        'grupo_fixo_mensal' => null,

        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$agosto->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Internet agosto',
            'valor' => 120,
            'data' => '2026-08-15',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Cartao',
            'status' => 'pendente',
            'observacao' => 'Somente agosto',
            'modo_edicao' => 'atual',
        ]
    );

    $response->assertOk();

    $agosto->refresh();
    $setembro->refresh();
    $regra->refresh();

    expect($agosto->descricao)
        ->toBe('Internet agosto');

    expect((float) $agosto->valor)
        ->toBe(120.0);

    expect($agosto->data->toDateString())
        ->toBe('2026-08-15');

    expect($agosto->forma_pagamento)
        ->toBe('Cartao');

    expect($agosto->observacao)
        ->toBe('Somente agosto');

    /*
    * Setembro deve continuar exatamente
    * com a regra antiga.
    */
    expect($setembro->descricao)
        ->toBe('Internet');

    expect((float) $setembro->valor)
        ->toBe(100.0);

    expect($setembro->data->toDateString())
        ->toBe('2026-09-10');

    expect($setembro->forma_pagamento)
        ->toBe('Pix');

    /*
    * A regra também não deve ser alterada,
    * pois a edição foi somente deste mês.
    */
    expect($regra->descricao)
        ->toBe('Internet');

    expect((float) $regra->valor)
        ->toBe(100.0);

    expect($regra->ativa)
        ->toBeTrue();
});

test('usuario pode editar despesa fixa deste mes e proximos pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $grupo = 'grupo-despesa-fixa-futuros';

    $regraAntiga = \App\Models\DespesaFixa::create([
        'user_id' => $user->id,
        'grupo_recorrencia' => $grupo,
        'descricao' => 'Academia',
        'valor' => 90,
        'data_inicio' => '2026-07-10',
        'dia_vencimento' => 10,
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'observacao' => null,
        'ativa' => true,
        'encerrada_em' => null,
    ]);

    $julho = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-07-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regraAntiga->id,
        'data_pagamento' => null,
    ]);

    $agosto = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regraAntiga->id,
        'data_pagamento' => null,
    ]);

    $setembro = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-09-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regraAntiga->id,
        'data_pagamento' => null,
    ]);

    $response = $this->putJson(
        "/api/v1/movimentacoes/{$agosto->id}",
        [
            'tipo' => 'despesa',
            'descricao' => 'Academia nova',
            'valor' => 110,
            'data' => '2026-08-15',
            'categoria' => 'Casa',
            'forma_pagamento' => 'Cartao',
            'status' => 'pendente',
            'observacao' => 'Novo valor',
            'modo_edicao' => 'futuros_fixa',
        ]
    );

    $response->assertOk();

    $regraAntiga->refresh();
    $julho->refresh();
    $agosto->refresh();
    $setembro->refresh();

    /*
    * Julho é histórico anterior e não muda.
    */
    expect($julho->descricao)
        ->toBe('Academia');

    expect((float) $julho->valor)
        ->toBe(90.0);

    expect($julho->data->toDateString())
        ->toBe('2026-07-10');

    /*
    * Agosto e setembro recebem a nova regra.
    */
    expect($agosto->descricao)
        ->toBe('Academia nova');

    expect((float) $agosto->valor)
        ->toBe(110.0);

    expect($agosto->data->toDateString())
        ->toBe('2026-08-15');

    expect($setembro->descricao)
        ->toBe('Academia nova');

    expect((float) $setembro->valor)
        ->toBe(110.0);

    expect($setembro->data->toDateString())
        ->toBe('2026-09-15');

    /*
    * A regra anterior deve ser encerrada.
    */
    expect($regraAntiga->ativa)
        ->toBeFalse();

    $novaRegra = \App\Models\DespesaFixa::where(
        'user_id',
        $user->id
    )
        ->where(
            'grupo_recorrencia',
            $grupo
        )
        ->where('ativa', true)
        ->firstOrFail();

    expect($novaRegra->descricao)
        ->toBe('Academia nova');

    expect((float) $novaRegra->valor)
        ->toBe(110.0);

    expect($novaRegra->dia_vencimento)
        ->toBe(15);

    expect($agosto->despesa_fixa_id)
        ->toBe($novaRegra->id);

    expect($setembro->despesa_fixa_id)
        ->toBe($novaRegra->id);
});

test('usuario pode excluir somente o mes atual de despesa fixa pela api', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $regra = \App\Models\DespesaFixa::create([
        'user_id' => $user->id,
        'grupo_recorrencia' => 'grupo-fixa-excluir-atual',
        'descricao' => 'Internet',
        'valor' => 100,
        'data_inicio' => '2026-08-10',
        'dia_vencimento' => 10,
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'observacao' => null,
        'ativa' => true,
        'encerrada_em' => null,
    ]);

    $agosto = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Internet',
        'valor' => 100,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,

        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,

        'despesa_fixa_id' => $regra->id,
        'entrada_fixa_id' => null,

        'data_pagamento' => null,
    ]);

    $setembro = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Internet',
        'valor' => 100,
        'data' => '2026-09-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,

        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,

        'despesa_fixa_id' => $regra->id,
        'entrada_fixa_id' => null,

        'data_pagamento' => null,
    ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$agosto->id}",
        [
            'modo_exclusao' => 'atual',
        ]
    );

    $response->assertOk();

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $agosto->id,
        ]
    );

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $setembro->id,
        ]
    );

    /*
    * A exceção impede o gerador de recriar
    * novamente agosto.
    */
    $this->assertDatabaseHas(
        'despesa_fixa_excecoes',
        [
            'despesa_fixa_id' => $regra->id,
            'competencia' => '2026-08-01 00:00:00',
        ]
    );

    $regra->refresh();

    expect($regra->ativa)
        ->toBeTrue();
});

test('api nao permite excluir mes ja pago de despesa fixa', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $regra = \App\Models\DespesaFixa::create([
        'user_id' => $user->id,
        'grupo_recorrencia' => 'grupo-fixa-paga',
        'descricao' => 'Energia',
        'valor' => 150,
        'data_inicio' => '2026-08-10',
        'dia_vencimento' => 10,
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'observacao' => null,
        'ativa' => true,
        'encerrada_em' => null,
    ]);

    $agosto = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Energia',
        'valor' => 150,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pago',
        'observacao' => null,

        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,

        'despesa_fixa_id' => $regra->id,
        'entrada_fixa_id' => null,

        'data_pagamento' => '2026-08-10',
    ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$agosto->id}",
        [
            'modo_exclusao' => 'atual',
        ]
    );

    $response->assertStatus(422);

    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $agosto->id,
            'status' => 'pago',
        ]
    );
});

test('usuario pode encerrar despesa fixa preservando lancamentos pagos', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs($user, ['mobile']);

    $regra = \App\Models\DespesaFixa::create([
        'user_id' => $user->id,
        'grupo_recorrencia' => 'grupo-fixa-encerrar',
        'descricao' => 'Academia',
        'valor' => 90,
        'data_inicio' => '2026-08-10',
        'dia_vencimento' => 10,
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'observacao' => null,
        'ativa' => true,
        'encerrada_em' => null,
    ]);

    $agosto = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-08-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regra->id,
        'data_pagamento' => null,
    ]);

    $setembro = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-09-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pago',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regra->id,
        'data_pagamento' => '2026-09-10',
    ]);

    $outubro = \App\Models\Movimentacao::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'descricao' => 'Academia',
        'valor' => 90,
        'data' => '2026-10-10',
        'categoria' => 'Casa',
        'forma_pagamento' => 'Pix',
        'status' => 'pendente',
        'observacao' => null,
        'parcelado' => false,
        'parcela_fixa' => true,
        'fixo_mensal' => false,
        'despesa_fixa_id' => $regra->id,
        'data_pagamento' => null,
    ]);

    $response = $this->deleteJson(
        "/api/v1/movimentacoes/{$agosto->id}",
        [
            'modo_exclusao' => 'encerrar_fixa',
        ]
    );

    $response->assertOk();

    /*
    * Pendentes de agosto em diante são removidas.
    */
    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $agosto->id,
        ]
    );

    $this->assertDatabaseMissing(
        'movimentacoes',
        [
            'id' => $outubro->id,
        ]
    );

    /*
    * Setembro, já pago, permanece como histórico.
    */
    $this->assertDatabaseHas(
        'movimentacoes',
        [
            'id' => $setembro->id,
            'status' => 'pago',
        ]
    );

    $regra->refresh();

    expect($regra->ativa)
        ->toBeFalse();

    expect(
        $regra->encerrada_em
            ? $regra->encerrada_em->toDateString()
            : null
    )->toBe('2026-08-01');
});
