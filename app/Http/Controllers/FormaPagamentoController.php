<?php

namespace App\Http\Controllers;

use App\Models\FormaPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FormaPagamentoController extends Controller
{
    public function index(): Response
    {
        $formasPagamento = FormaPagamento::where('user_id', Auth::id())
            ->orderBy('nome')
            ->get()
            ->map(function ($formaPagamento) {
                return [
                    'id' => $formaPagamento->id,
                    'nome' => $formaPagamento->nome,
                    'ativo' => $formaPagamento->ativo,
                ];
            });

        return Inertia::render('formas-pagamento/index', [
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('formas_pagamento')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
        ]);

        FormaPagamento::create([
            'user_id' => Auth::id(),
            'nome' => $dados['nome'],
            'ativo' => true,
        ]);

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento cadastrada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $formaPagamento = FormaPagamento::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $dados = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('formas_pagamento', 'nome')
                    ->where(function ($query) {
                        return $query->where('user_id', Auth::id());
                    })
                    ->ignore($formaPagamento->id),
            ],
        ]);

        $formaPagamento->update([
            'nome' => $dados['nome'],
        ]);

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $formaPagamento = FormaPagamento::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $formaPagamento->delete();

        return redirect()
            ->route('formas-pagamento.index')
            ->with('success', 'Forma de pagamento excluída com sucesso.');
    }
}
