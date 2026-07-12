<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
                'receber_aviso_whatsapp' => $user->receber_aviso_whatsapp,
                'telefone_whatsapp' => $user->telefone_whatsapp,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $receberWhatsapp = $request->boolean(
            'receber_aviso_whatsapp'
        );

        $telefone = preg_replace(
            '/\D+/',
            '',
            (string) $request->input('telefone_whatsapp', '')
        );

        // Quando for informado somente DDD + número,
        // adiciona automaticamente o código do Brasil.
        if (
            $telefone !== '' &&
            in_array(strlen($telefone), [10, 11], true)
        ) {
            $telefone = '55' . $telefone;
        }

        $request->merge([
            'receber_aviso_email' => $request->boolean(
                'receber_aviso_email'
            ),

            'receber_aviso_whatsapp' => $receberWhatsapp,

            'telefone_whatsapp' => $telefone !== ''
                ? $telefone
                : null,
        ]);

        $validated = $request->validate(
            [
                'receber_aviso_email' => [
                    'required',
                    'boolean',
                ],

                'receber_aviso_whatsapp' => [
                    'required',
                    'boolean',
                ],

                'telefone_whatsapp' => [
                    Rule::requiredIf($receberWhatsapp),
                    'nullable',
                    'string',
                    'regex:/^55\d{10,11}$/',
                ],
            ],
            [
                'telefone_whatsapp.required' =>
                    'Informe o número do WhatsApp para receber os avisos.',

                'telefone_whatsapp.regex' =>
                    'Informe um número de WhatsApp brasileiro válido com DDD.',
            ]
        );

        $user = $request->user();

        $user->update([
            'receber_aviso_email' =>
                $validated['receber_aviso_email'],

            'receber_aviso_whatsapp' =>
                $validated['receber_aviso_whatsapp'],

            'telefone_whatsapp' => $receberWhatsapp
                ? $validated['telefone_whatsapp']
                : null,

            'whatsapp_consentimento_em' => $receberWhatsapp
                ? ($user->whatsapp_consentimento_em ?? now())
                : null,

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
