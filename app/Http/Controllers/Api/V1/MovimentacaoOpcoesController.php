<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MovimentacaoOpcoesRequest;
use App\Models\Categoria;
use App\Models\FormaPagamento;
use Illuminate\Http\JsonResponse;

class MovimentacaoOpcoesController extends Controller
{
    /**
     * Retorna as opções disponíveis para o
     * cadastro de movimentações no aplicativo.
     */
    public function index(
        MovimentacaoOpcoesRequest $request
    ): JsonResponse {
        $user = $request->user();
        $dados = $request->validated();

        $tipo = $dados['tipo'];

        $categorias = Categoria::query()
            ->where('user_id', $user->id)
            ->where('tipo', $tipo)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(function (Categoria $categoria): array {
                return [
                    'id' => $categoria->id,
                    'nome' => $categoria->nome,
                ];
            })
            ->values();

        $formasPagamento = collect();

        if ($tipo === 'despesa') {
            $formasPagamento = FormaPagamento::query()
                ->where('user_id', $user->id)
                ->where('ativo', true)
                ->orderBy('nome')
                ->get()
                ->map(
                    function (
                        FormaPagamento $formaPagamento
                    ): array {
                        return [
                            'id' => $formaPagamento->id,
                            'nome' => $formaPagamento->nome,
                        ];
                    }
                )
                ->values();
        }

        return response()->json([
            'tipo' => $tipo,
            'categorias' => $categorias,
            'formas_pagamento' => $formasPagamento,
        ]);
    }
}
