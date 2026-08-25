<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateDadosUsuarioRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DadosUsuarioController extends Controller
{
    /**
     * Retorna os dados do usuário autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'receber_aviso_email' => $user->receber_aviso_email,

                // WhatsApp permanece indisponível,
                // seguindo o comportamento atual do site.
                'receber_aviso_whatsapp' => false,
                'telefone_whatsapp' => null,
            ],
        ]);
    }
    /**
     * Atualiza os dados do usuário autenticado.
     */
    public function update(
        UpdateDadosUsuarioRequest $request
    ): JsonResponse {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->receber_aviso_email =
            $data['receber_aviso_email'];

        // WhatsApp permanece indisponível,
        // seguindo o comportamento atual do site.
        $user->receber_aviso_whatsapp = false;
        $user->telefone_whatsapp = null;
        $user->whatsapp_consentimento_em = null;

        $user->preferencias_notificacao_definidas_em =
            $user->preferencias_notificacao_definidas_em
                ?? now();

        $user->save();

        return response()->json([
            'message' => 'Dados do usuário atualizados com sucesso.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'receber_aviso_email' =>
                    $user->receber_aviso_email,
                'receber_aviso_whatsapp' => false,
                'telefone_whatsapp' => null,
            ],
        ]);
    }
}
