<?php

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('categorias rejeitam acesso sem autenticacao', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson(
        '/api/v1/categorias'
    )->assertUnauthorized();

    $this->postJson(
        '/api/v1/categorias',
        [
            'tipo' => 'despesa',
            'nome' => 'Casa',
        ]
    )->assertUnauthorized();

    $this->putJson(
        '/api/v1/categorias/1',
        [
            'tipo' => 'despesa',
            'nome' => 'Casa',
        ]
    )->assertUnauthorized();

    $this->deleteJson(
        '/api/v1/categorias/1'
    )->assertUnauthorized();
});

test('usuario autenticado consulta somente suas categorias ativas', function () {
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
        'nome' => 'Carro',
        'ativo' => true,
    ]);

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'entrada',
        'nome' => 'Salário',
        'ativo' => true,
    ]);

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Categoria inativa',
        'ativo' => false,
    ]);

    Categoria::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'nome' => 'Outro usuário',
        'ativo' => true,
    ]);

    $response = $this->getJson(
        '/api/v1/categorias'
    );

    $response
        ->assertOk()
        ->assertJsonCount(
            2,
            'categorias'
        )
        ->assertJsonPath(
            'categorias.0.tipo',
            'despesa'
        )
        ->assertJsonPath(
            'categorias.0.nome',
            'Carro'
        )
        ->assertJsonPath(
            'categorias.1.tipo',
            'entrada'
        )
        ->assertJsonPath(
            'categorias.1.nome',
            'Salário'
        )
        ->assertJsonStructure([
            'categorias' => [
                '*' => [
                    'id',
                    'tipo',
                    'nome',
                    'ativo',
                ],
            ],
        ])
        ->assertJsonMissing([
            'nome' => 'Categoria inativa',
        ])
        ->assertJsonMissing([
            'nome' => 'Outro usuário',
        ]);
});

test('usuario bloqueado nao pode consultar categorias', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->getJson(
        '/api/v1/categorias'
    );

    $response
        ->assertForbidden()
        ->assertJson([
            'message' =>
                'Esta conta está bloqueada. Entre em contato com o administrador.',

            'code' => 'ACCOUNT_BLOCKED',
        ]);
});

test('usuario autenticado pode cadastrar categoria', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $response = $this->postJson(
        '/api/v1/categorias',
        [
            'tipo' => 'despesa',
            'nome' => 'Mercado',
        ]
    );

    $response
        ->assertCreated()
        ->assertJsonPath(
            'message',
            'Categoria cadastrada com sucesso.'
        )
        ->assertJsonPath(
            'categoria.tipo',
            'despesa'
        )
        ->assertJsonPath(
            'categoria.nome',
            'Mercado'
        )
        ->assertJsonPath(
            'categoria.ativo',
            true
        );

    $this->assertDatabaseHas(
        'categorias',
        [
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'nome' => 'Mercado',
            'ativo' => true,
        ]
    );
});

test('mesmo nome pode existir em tipos diferentes', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Outros',
        'ativo' => true,
    ]);

    $response = $this->postJson(
        '/api/v1/categorias',
        [
            'tipo' => 'entrada',
            'nome' => 'Outros',
        ]
    );

    $response->assertCreated();

    $this->assertDatabaseHas(
        'categorias',
        [
            'user_id' => $user->id,
            'tipo' => 'despesa',
            'nome' => 'Outros',
        ]
    );

    $this->assertDatabaseHas(
        'categorias',
        [
            'user_id' => $user->id,
            'tipo' => 'entrada',
            'nome' => 'Outros',
        ]
    );
});

test('nao permite categoria duplicada para mesmo usuario e tipo', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

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

    $response = $this->postJson(
        '/api/v1/categorias',
        [
            'tipo' => 'despesa',
            'nome' => 'Casa',
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'nome',
        ]);
});

test('categoria inativa continua reservando o nome conforme regra do site', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Combustível',
        'ativo' => false,
    ]);

    $response = $this->postJson(
        '/api/v1/categorias',
        [
            'tipo' => 'despesa',
            'nome' => 'Combustível',
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'nome',
        ]);
});

test('usuario pode editar categoria propria', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $categoria = Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Carro',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/categorias/{$categoria->id}",
        [
            'tipo' => 'entrada',
            'nome' => 'Venda',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Categoria atualizada com sucesso.'
        )
        ->assertJsonPath(
            'categoria.tipo',
            'entrada'
        )
        ->assertJsonPath(
            'categoria.nome',
            'Venda'
        );

    $this->assertDatabaseHas(
        'categorias',
        [
            'id' => $categoria->id,
            'user_id' => $user->id,
            'tipo' => 'entrada',
            'nome' => 'Venda',
            'ativo' => true,
        ]
    );
});

test('edicao permite manter o proprio nome da categoria', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $categoria = Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Mercado',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/categorias/{$categoria->id}",
        [
            'tipo' => 'despesa',
            'nome' => 'Mercado',
        ]
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'categoria.nome',
            'Mercado'
        );
});

test('edicao rejeita nome duplicado no mesmo tipo', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

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

    $categoria = Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Carro',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/categorias/{$categoria->id}",
        [
            'tipo' => 'despesa',
            'nome' => 'Casa',
        ]
    );

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'nome',
        ]);

    $categoria->refresh();

    expect($categoria->nome)
        ->toBe('Carro');
});

test('usuario nao pode editar categoria de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $categoria = Categoria::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'nome' => 'Protegida',
        'ativo' => true,
    ]);

    $response = $this->putJson(
        "/api/v1/categorias/{$categoria->id}",
        [
            'tipo' => 'entrada',
            'nome' => 'Tentativa',
        ]
    );

    $response->assertNotFound();

    $categoria->refresh();

    expect($categoria->nome)
        ->toBe('Protegida');

    expect($categoria->tipo)
        ->toBe('despesa');
});

test('excluir categoria apenas inativa o registro', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $categoria = Categoria::create([
        'user_id' => $user->id,
        'tipo' => 'despesa',
        'nome' => 'Excluir teste',
        'ativo' => true,
    ]);

    $response = $this->deleteJson(
        "/api/v1/categorias/{$categoria->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Categoria excluída com sucesso.'
        );

    $this->assertDatabaseHas(
        'categorias',
        [
            'id' => $categoria->id,
            'user_id' => $user->id,
            'nome' => 'Excluir teste',
            'ativo' => false,
        ]
    );

    $response = $this->getJson(
        '/api/v1/categorias'
    );

    $response
        ->assertOk()
        ->assertJsonMissing([
            'nome' => 'Excluir teste',
        ]);
});

test('usuario nao pode excluir categoria de outro usuario', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    Sanctum::actingAs(
        $user,
        ['mobile']
    );

    $categoria = Categoria::create([
        'user_id' => $outroUsuario->id,
        'tipo' => 'despesa',
        'nome' => 'Não excluir',
        'ativo' => true,
    ]);

    $response = $this->deleteJson(
        "/api/v1/categorias/{$categoria->id}"
    );

    $response->assertNotFound();

    $this->assertDatabaseHas(
        'categorias',
        [
            'id' => $categoria->id,
            'user_id' => $outroUsuario->id,
            'ativo' => true,
        ]
    );
});
