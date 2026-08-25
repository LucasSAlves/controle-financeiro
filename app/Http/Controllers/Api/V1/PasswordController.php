<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Atualiza a senha do usuário autenticado.
     */
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Senha atualizada com sucesso.',
        ]);
    }
}
