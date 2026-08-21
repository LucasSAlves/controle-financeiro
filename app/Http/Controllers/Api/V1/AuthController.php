<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Cria uma nova conta e autentica o usuário no aplicativo.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        $token = $user
            ->createToken($data['device_name'], ['mobile'])
            ->plainTextToken;

        return response()->json([
            'message' => 'Conta criada com sucesso.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->userData($user),
        ], 201);
    }

    /**
     * Autentica o usuário e cria um token para o dispositivo.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [
                    'E-mail ou senha inválidos.',
                ],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Esta conta está bloqueada. Entre em contato com o administrador.',
                'code' => 'ACCOUNT_BLOCKED',
            ], 403);
        }

        $token = $user
            ->createToken($data['device_name'], ['mobile'])
            ->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->userData($user),
        ]);
    }

    /**
     * Retorna o usuário autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userData($request->user()),
        ]);
    }

    /**
     * Revoga somente o token utilizado pelo aparelho atual.
     */
    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Define os dados seguros enviados ao aplicativo.
     *
     * @return array<string, int|string>
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
