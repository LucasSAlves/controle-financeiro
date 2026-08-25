<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_alterar_a_senha(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAtual@2026!'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/password', [
            'current_password' => 'SenhaAtual@2026!',
            'password' => 'NovaSenha@2026!',
            'password_confirmation' => 'NovaSenha@2026!',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Senha atualizada com sucesso.',
            ]);

        $user->refresh();

        $this->assertTrue(
            Hash::check('NovaSenha@2026!', $user->password)
        );
    }

    public function test_senha_atual_incorreta_e_rejeitada(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAtual@2026!'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/password', [
            'current_password' => 'SenhaErrada@2026!',
            'password' => 'NovaSenha@2026!',
            'password_confirmation' => 'NovaSenha@2026!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'current_password',
            ]);

        $user->refresh();

        $this->assertTrue(
            Hash::check('SenhaAtual@2026!', $user->password)
        );
    }

    public function test_confirmacao_da_nova_senha_precisa_conferir(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SenhaAtual@2026!'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/password', [
            'current_password' => 'SenhaAtual@2026!',
            'password' => 'NovaSenha@2026!',
            'password_confirmation' => 'OutraSenha@2026!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_usuario_nao_autenticado_nao_pode_alterar_senha(): void
    {
        $response = $this->patchJson('/api/v1/password', [
            'current_password' => 'SenhaAtual@2026!',
            'password' => 'NovaSenha@2026!',
            'password_confirmation' => 'NovaSenha@2026!',
        ]);

        $response->assertUnauthorized();
    }
}
