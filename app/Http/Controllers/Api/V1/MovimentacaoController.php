<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MovimentacaoIndexRequest;
use App\Models\Movimentacao;
use App\Services\GerarMovimentacoesFixasAteCompetencia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class MovimentacaoController extends Controller
{
    /**
     * Retorna as movimentações do mês selecionado.
     */
    public function index(
        MovimentacaoIndexRequest $request,
        GerarMovimentacoesFixasAteCompetencia $geradorHistoricoFixo
    ): JsonResponse {
        $user = $request->user();
        $dados = $request->validated();

        $mesSelecionado = $dados['mes']
            ?? now()->format('Y-m');

        $tipoSelecionado = $dados['tipo']
            ?? 'todos';

        $statusSelecionado = $dados['status']
            ?? 'todos';

        $inicioDoMes = Carbon::createFromFormat(
            'Y-m-d',
            "{$mesSelecionado}-01"
        )->startOfMonth();

        $fimDoMes = $inicioDoMes
            ->copy()
            ->endOfMonth();

        /*
         * Garante que as despesas e entradas fixas estejam
         * geradas até a competência consultada no aplicativo.
         */
        $geradorHistoricoFixo->gerar(
            (int) $user->id,
            $fimDoMes
        );

        $queryMovimentacoes = Movimentacao::query()
            ->where('user_id', $user->id)
            ->whereBetween('data', [
                $inicioDoMes->toDateString(),
                $fimDoMes->toDateString(),
            ]);

        if ($tipoSelecionado !== 'todos') {
            $queryMovimentacoes->where(
                'tipo',
                $tipoSelecionado
            );
        }

        if ($statusSelecionado !== 'todos') {
            $queryMovimentacoes->where(
                'status',
                $statusSelecionado
            );
        }

        $movimentacoes = $queryMovimentacoes
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get()
            ->map(function (
                Movimentacao $movimentacao
            ): array {
                return [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo,
                    'descricao' => $movimentacao->descricao,
                    'valor' => (float) $movimentacao->valor,

                    'data' => $movimentacao->data
                        ->format('Y-m-d'),

                    'data_pagamento' =>
                        $movimentacao->data_pagamento
                            ? $movimentacao->data_pagamento
                                ->format('Y-m-d')
                            : null,

                    'categoria' => $movimentacao->categoria,

                    'forma_pagamento' =>
                        $movimentacao->forma_pagamento,

                    'status' => $movimentacao->status,
                    'observacao' => $movimentacao->observacao,

                    'parcelado' =>
                        (bool) $movimentacao->parcelado,

                    'parcela_fixa' =>
                        (bool) $movimentacao->parcela_fixa,

                    'fixo_mensal' =>
                        (bool) $movimentacao->fixo_mensal,

                    'despesa_fixa_id' =>
                        $movimentacao->despesa_fixa_id,

                    'entrada_fixa_id' =>
                        $movimentacao->entrada_fixa_id,

                    'parcela_atual' =>
                        $movimentacao->parcela_atual,

                    'total_parcelas' =>
                        $movimentacao->total_parcelas,

                    'grupo_parcelamento' =>
                        $movimentacao->grupo_parcelamento,

                    'mes_atual' =>
                        $movimentacao->mes_atual,

                    'total_meses' =>
                        $movimentacao->total_meses,

                    'grupo_fixo_mensal' =>
                        $movimentacao->grupo_fixo_mensal,
                ];
            })
            ->values();

        $consultaDoMes = Movimentacao::query()
            ->where('user_id', $user->id)
            ->whereBetween('data', [
                $inicioDoMes->toDateString(),
                $fimDoMes->toDateString(),
            ]);

        $totalEntradas = (clone $consultaDoMes)
            ->where('tipo', 'entrada')
            ->sum('valor');

        $totalDespesas = (clone $consultaDoMes)
            ->where('tipo', 'despesa')
            ->sum('valor');

        $totalPendentes = (clone $consultaDoMes)
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->sum('valor');

        return response()->json([
            'filtros' => [
                'mes' => $mesSelecionado,
                'tipo' => $tipoSelecionado,
                'status' => $statusSelecionado,
            ],

            'resumo' => [
                'entradas' => (float) $totalEntradas,
                'despesas' => (float) $totalDespesas,

                'saldo' => (float) (
                    $totalEntradas - $totalDespesas
                ),

                'pendentes' => (float) $totalPendentes,
            ],

            'movimentacoes' => $movimentacoes,
        ]);
    }
}
