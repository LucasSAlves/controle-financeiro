<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FormaPagamentoStoreRequest;
use App\Http\Requests\Api\V1\FormaPagamentoUpdateRequest;
use App\Models\FormaPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormaPagamentoController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $formasPagamento = FormaPagamento::query()
            ->where('user_id', $user->id)
            ->orderBy('nome')
            ->get()
            ->map(function (
                FormaPagamento $formaPagamento
            ): array {
                return [
                    'id' => $formaPagamento->id,
                    'nome' => $formaPagamento->nome,
                    'ativo' => $formaPagamento->ativo,
                ];
            })
            ->values();

        return response()->json([
            'formas_pagamento' => $formasPagamento,
        ]);
    }

    public function store(
        FormaPagamentoStoreRequest $request
    ): JsonResponse {
        $user = $request->user();
        $dados = $request->validated();

        $formaPagamento = FormaPagamento::create([
            'user_id' => $user->id,
            'nome' => $dados['nome'],
            'ativo' => true,
        ]);

        return response()->json([
            'message' =>
                'Forma de pagamento cadastrada com sucesso.',

            'forma_pagamento' => [
                'id' => $formaPagamento->id,
                'nome' => $formaPagamento->nome,
                'ativo' => $formaPagamento->ativo,
            ],
        ], 201);
    }

    public function update(
        FormaPagamentoUpdateRequest $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        $formaPagamento = FormaPagamento::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $dados = $request->validated();

        $formaPagamento->update([
            'nome' => $dados['nome'],
        ]);

        return response()->json([
            'message' =>
                'Forma de pagamento atualizada com sucesso.',

            'forma_pagamento' => [
                'id' => $formaPagamento->id,
                'nome' => $formaPagamento->nome,
                'ativo' => $formaPagamento->ativo,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        $formaPagamento = FormaPagamento::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $formaPagamento->delete();

        return response()->json([
            'message' =>
                'Forma de pagamento excluída com sucesso.',
        ]);
    }
}
