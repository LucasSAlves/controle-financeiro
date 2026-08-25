<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DadosUsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_consultar_seus_dados(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
            'receber_aviso_email' => true,
            'is_active' => true,
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/dados-usuario');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Usuário Teste')
            ->assertJsonPath('user.email', 'usuario@teste.com')
            ->assertJsonPath('user.receber_aviso_email', true)
            ->assertJsonPath('user.receber_aviso_whatsapp', false)
            ->assertJsonPath('user.telefone_whatsapp', null)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'receber_aviso_email',
                    'receber_aviso_whatsapp',
                    'telefone_whatsapp',
                ],
            ]);
    }

    public function test_rota_dados_usuario_rejeita_requisicao_sem_token(): void
    {
        $this
            ->getJson('/api/v1/dados-usuario')
            ->assertUnauthorized();
    }

    public function test_usuario_pode_atualizar_seus_dados(): void
    {
        $user = User::factory()->create([
            'name' => 'Nome Antigo',
            'email' => 'antigo@teste.com',
            'email_verified_at' => now(),
            'receber_aviso_email' => false,
            'is_active' => true,
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->patchJson('/api/v1/dados-usuario', [
                'name' => '  Nome Atualizado  ',
                'email' => '  NOVO@TESTE.COM  ',
                'receber_aviso_email' => true,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Dados do usuário atualizados com sucesso.'
            )
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Nome Atualizado')
            ->assertJsonPath('user.email', 'novo@teste.com')
            ->assertJsonPath(
                'user.receber_aviso_email',
                true
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nome Atualizado',
            'email' => 'novo@teste.com',
            'receber_aviso_email' => true,
            'email_verified_at' => null,
        ]);
    }

    public function test_usuario_pode_manter_o_proprio_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
            'receber_aviso_email' => true,
            'is_active' => true,
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $this
            ->withToken($token)
            ->patchJson('/api/v1/dados-usuario', [
                'name' => 'Usuário Atualizado',
                'email' => 'usuario@teste.com',
                'receber_aviso_email' => false,
            ])
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                'usuario@teste.com'
            )
            ->assertJsonPath(
                'user.receber_aviso_email',
                false
            );
    }

    public function test_atualizacao_rejeita_email_de_outro_usuario(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario@teste.com',
            'is_active' => true,
        ]);

        User::factory()->create([
            'email' => 'ocupado@teste.com',
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->patchJson('/api/v1/dados-usuario', [
                'name' => 'Usuário Teste',
                'email' => 'ocupado@teste.com',
                'receber_aviso_email' => true,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath(
                'errors.email.0',
                'Este e-mail já está cadastrado.'
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'usuario@teste.com',
        ]);
    }
}
