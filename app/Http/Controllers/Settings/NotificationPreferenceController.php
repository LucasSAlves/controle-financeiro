<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/notificacoes', [
            'preferencias' => [
                'receber_aviso_email' => $user->receber_aviso_email,

                // WhatsApp temporariamente indisponível.
                'receber_aviso_whatsapp' => false,
                'telefone_whatsapp' => null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'receber_aviso_email' => $request->boolean(
                'receber_aviso_email'
            ),
        ]);

        $validated = $request->validate(
            [
                'receber_aviso_email' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'receber_aviso_email.required' =>
                    'Informe se deseja receber avisos por e-mail.',
            ]
        );

        $user = $request->user();

        $user->update([
            'receber_aviso_email' =>
                $validated['receber_aviso_email'],

            // Mantemos o WhatsApp desativado no banco.
            'receber_aviso_whatsapp' => false,
            'telefone_whatsapp' => null,
            'whatsapp_consentimento_em' => null,

            'preferencias_notificacao_definidas_em' =>
                $user->preferencias_notificacao_definidas_em
                    ?? now(),
        ]);

        return back()->with(
            'success',
            'Preferências de notificação atualizadas com sucesso.'
        );
    }
}
