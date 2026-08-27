<?php

use App\Models\FormaPagamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('formas de pagamento rejeitam acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson(
        '/api/v1/formas-pagamento'
    )->assertUnauthorized();

    $this->postJson(
        '/api/v1/formas-pagamento',
        [
            'nome' => 'Cartão',
        ]
    )->assertUnauthorized();

    $this->putJson(
        '/api/v1/formas-pagamento/1',
        [
            'nome' => 'Cartão',
        ]
    )->assertUnauthorized();

    $this->deleteJson(
        '/api/v1/formas-pagamento/1'
    )->assertUnauthorized();
});

test('usuario autenticado consulta somente suas formas de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Dinheiro',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Cartão de crédito',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Forma inativa',
        'ativo' => false,
    ]);

    FormaPagamento::create([
        'user_id' => $outroUsuario->id,
        'nome' => 'Outro usuário',
        'ativo' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/formas-pagamento'
    );

    $response
        ->assertOk()
        ->assertJsonCount(
            3,
            'formas_pagamento'
        )
        ->assertJsonStructure([
            'formas_pagamento' => [
                '*' => [
                    'id',
                    'nome',
                    'ativo',
                ],
            ],
        ])
        ->assertJsonFragment([
            'nome' => 'Dinheiro',
            'ativo' => true,
        ])
        ->assertJsonFragment([
            'nome' => 'Cartão de crédito',
            'ativo' => true,
        ])
        ->assertJsonFragment([
            'nome' => 'Forma inativa',
            'ativo' => false,
        ])
        ->assertJsonMissing([
            'nome' => 'Outro usuário',
        ]);
});

test('formas de pagamento sao ordenadas por nome', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Dinheiro',
        'ativo' => true,
    ]);

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Boleto',
        'ativo' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/formas-pagamento'
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'formas_pagamento.0.nome',
            'Boleto'
        )
        ->assertJsonPath(
            'formas_pagamento.1.nome',
            'Dinheiro'
        )
        ->assertJsonPath(
            'formas_pagamento.2.nome',
            'Pix'
        );
});

test('usuario bloqueado nao pode consultar formas de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/formas-pagamento'
    );

    $response
        ->assertForbidden()
        ->assertJson([
            'message' =>
                'Esta conta está bloqueada. Entre em contato com o administrador.',

            'code' => 'ACCOUNT_BLOCKED',
        ]);
});

test('usuario autenticado pode cadastrar forma de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/formas-pagamento',
        [
            'nome' => 'Cartão de crédito',
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Forma de pagamento cadastrada com sucesso.'
        )
        ->assertJsonPath(
            'forma_pagamento.nome',
            'Cartão de crédito'
        )
        ->assertJsonPath(
            'forma_pagamento.ativo',
            true
        );

    $this->assertDatabaseHas(
        'formas_pagamento',
        [
            'user_id' => $user->id,
            'nome' => 'Cartão de crédito',
            'ativo' => true,
        ]
    );
});

test('nao permite forma de pagamento duplicada para mesmo usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    $response = $this->postJson(
        '/api/v1/formas-pagamento',
        [
            'nome' => 'Pix',
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'nome',
        ]);
});

test('mesmo nome pode existir para usuarios diferentes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    FormaPagamento::create([
        'user_id' => $outroUsuario->id,
        'nome' => 'Dinheiro',
        'ativo' => true,
    ]);

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/formas-pagamento',
        [
            'nome' => 'Dinheiro',
        ]
    );

    $response->assertCreated();

    $this->assertDatabaseHas(
        'formas_pagamento',
        [
            'user_id' => $user->id,
            'nome' => 'Dinheiro',
        ]
    );

    $this->assertDatabaseHas(
        'formas_pagamento',
        [
            'user_id' => $outroUsuario->id,
            'nome' => 'Dinheiro',
        ]
    );
});

test('usuario pode editar forma de pagamento propria', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $formaPagamento = FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Cartão',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}",
        [
            'nome' => 'Cartão de crédito',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Forma de pagamento atualizada com sucesso.'
        )
        ->assertJsonPath(
            'forma_pagamento.nome',
            'Cartão de crédito'
        )
        ->assertJsonPath(
            'forma_pagamento.ativo',
            true
        );

    $this->assertDatabaseHas(
        'formas_pagamento',
        [
            'id' => $formaPagamento->id,
            'user_id' => $user->id,
            'nome' => 'Cartão de crédito',
            'ativo' => true,
        ]
    );
});

test('edicao permite manter o proprio nome da forma de pagamento', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $formaPagamento = FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}",
        [
            'nome' => 'Pix',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'forma_pagamento.nome',
            'Pix'
        );
});

test('edicao rejeita nome duplicado para mesmo usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Dinheiro',
        'ativo' => true,
    ]);

    $formaPagamento = FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Pix',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}",
        [
            'nome' => 'Dinheiro',
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'nome',
        ]);

    $formaPagamento->refresh();

    expect($formaPagamento->nome)
        ->toBe('Pix');
});

test('usuario nao pode editar forma de pagamento de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $formaPagamento = FormaPagamento::create([
        'user_id' => $outroUsuario->id,
        'nome' => 'Protegida',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}",
        [
            'nome' => 'Tentativa',
        ]
    );

    $response->assertNotFound();

    $formaPagamento->refresh();

    expect($formaPagamento->nome)
        ->toBe('Protegida');
});

test('excluir forma de pagamento remove definitivamente o registro', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $formaPagamento = FormaPagamento::create([
        'user_id' => $user->id,
        'nome' => 'Excluir teste',
        'ativo' => true,
    ]);

    $response = $this->deleteJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Forma de pagamento excluída com sucesso.'
        );

    $this->assertDatabaseMissing(
        'formas_pagamento',
        [
            'id' => $formaPagamento->id,
            'user_id' => $user->id,
        ]
    );
});

test('usuario nao pode excluir forma de pagamento de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $formaPagamento = FormaPagamento::create([
        'user_id' => $outroUsuario->id,
        'nome' => 'Não excluir',
        'ativo' => true,
    ]);

    $response = $this->deleteJson(
        "/api/v1/formas-pagamento/{$formaPagamento->id}"
    );

    $response->assertNotFound();

    $this->assertDatabaseHas(
        'formas_pagamento',
        [
            'id' => $formaPagamento->id,
            'user_id' => $outroUsuario->id,
            'nome' => 'Não excluir',
        ]
    );
});
