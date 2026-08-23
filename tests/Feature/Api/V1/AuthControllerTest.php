<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_ativo_pode_fazer_login_e_receber_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
            'password' => 'senha-teste-123',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'usuario@teste.com',
            'password' => 'senha-teste-123',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login realizado com sucesso.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Usuário Teste')
            ->assertJsonPath('user.email', 'usuario@teste.com')
            ->assertJsonStructure([
                'message',
                'token_type',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Expo - testes',
        ]);
    }

    public function test_login_rejeita_senha_incorreta(): void
    {
        User::factory()->create([
            'email' => 'usuario@teste.com',
            'password' => 'senha-correta',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'usuario@teste.com',
            'password' => 'senha-incorreta',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath(
                'errors.email.0',
                'E-mail ou senha inválidos.'
            );

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejeita_usuario_bloqueado(): void
    {
        User::factory()->create([
            'email' => 'bloqueado@teste.com',
            'password' => 'senha-teste-123',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'bloqueado@teste.com',
            'password' => 'senha-teste-123',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Esta conta está bloqueada. Entre em contato com o administrador.',
                'code' => 'ACCOUNT_BLOCKED',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_usuario_autenticado_pode_consultar_seus_dados(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
            'is_active' => true,
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Usuário Teste')
            ->assertJsonPath('user.email', 'usuario@teste.com');
    }

    public function test_rota_protegida_rejeita_requisicao_sem_token(): void
    {
        $this
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_usuario_pode_encerrar_sessao_do_dispositivo_atual(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $createdToken = $user->createToken(
            'Expo - testes',
            ['mobile']
        );

        $tokenId = $createdToken->accessToken->id;

        $response = $this
            ->withToken($createdToken->plainTextToken)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Logout realizado com sucesso.',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_token_de_usuario_bloqueado_deixa_de_acessar_a_api(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $token = $user
            ->createToken('Expo - testes', ['mobile'])
            ->plainTextToken;

        $user->forceFill([
            'is_active' => false,
        ])->save();

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Esta conta está bloqueada. Entre em contato com o administrador.',
                'code' => 'ACCOUNT_BLOCKED',
            ]);
    }

        public function test_visitante_pode_criar_conta_e_receber_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Novo Usuário',
            'email' => '  NOVO@TESTE.COM  ',
            'password' => 'senha-teste-123',
            'password_confirmation' => 'senha-teste-123',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Conta criada com sucesso.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.name', 'Novo Usuário')
            ->assertJsonPath('user.email', 'novo@teste.com')
            ->assertJsonStructure([
                'message',
                'token_type',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@teste.com',
        ]);

        $user = User::query()
            ->where('email', 'novo@teste.com')
            ->firstOrFail();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Expo - testes',
        ]);

        $token = $response->json('token');

        $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'novo@teste.com');
    }

    public function test_cadastro_rejeita_email_ja_cadastrado(): void
    {
        User::factory()->create([
            'email' => 'existente@teste.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'existente@teste.com',
            'password' => 'senha-teste-123',
            'password_confirmation' => 'senha-teste-123',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath(
                'errors.email.0',
                'Este e-mail já está cadastrado.'
            );

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cadastro_rejeita_confirmacao_de_senha_incorreta(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Novo Usuário',
            'email' => 'novo@teste.com',
            'password' => 'senha-teste-123',
            'password_confirmation' => 'senha-diferente',
            'device_name' => 'Expo - testes',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath(
                'errors.password.0',
                'A confirmação da senha não confere.'
            );

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_usuario_pode_solicitar_link_de_recuperacao_de_senha(): void
{
    $user = User::factory()->create([
        'email' => 'recuperacao@teste.com',
    ]);

    \Illuminate\Support\Facades\Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'recuperacao@teste.com',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Se o e-mail informado estiver cadastrado, você receberá um link de redefinição de senha em breve.'
        );

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $user,
        \Illuminate\Auth\Notifications\ResetPassword::class
    );
}

    public function test_recuperacao_de_senha_nao_revela_se_email_existe(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'naoexiste@teste.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Se o e-mail informado estiver cadastrado, você receberá um link de redefinição de senha em breve.'
            );

        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }
}
