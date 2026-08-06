<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Movimentacao;
use App\Services\CalcularSaldoAcumulado;
use App\Services\GerarMovimentacoesFixasAteCompetencia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Retorna o resumo financeiro do mês selecionado.
     */
    public function index(
        Request $request,
        GerarMovimentacoesFixasAteCompetencia $geradorHistoricoFixo,
        CalcularSaldoAcumulado $calculadorSaldo
    ): JsonResponse {
        $user = $request->user();

        $mesSelecionado = $request->query(
            'mes',
            now()->format('Y-m')
        );

        if (
            ! is_string($mesSelecionado)
            || ! preg_match(
                '/^\d{4}-(0[1-9]|1[0-2])$/',
                $mesSelecionado
            )
        ) {
            return response()->json([
                'message' => 'O mês informado é inválido.',
                'errors' => [
                    'mes' => [
                        'Informe o mês no formato AAAA-MM.',
                    ],
                ],
            ], 422);
        }

        $inicioDoMes = Carbon::createFromFormat(
            'Y-m-d',
            "{$mesSelecionado}-01"
        )->startOfMonth();

        $fimDoMes = $inicioDoMes
            ->copy()
            ->endOfMonth();

        /*
         * Garante que despesas e entradas fixas estejam geradas
         * até o mês consultado pelo aplicativo.
         */
        $geradorHistoricoFixo->gerar(
            (int) $user->id,
            $fimDoMes
        );

        /*
         * Usa exatamente o mesmo cálculo de saldo acumulado
         * utilizado atualmente pelo site.
         */
        $saldoAcumulado = $calculadorSaldo->calcular(
            (int) $user->id,
            $inicioDoMes,
            $fimDoMes
        );

        $ultimasMovimentacoes = Movimentacao::query()
            ->where('user_id', $user->id)
            ->whereBetween('data', [
                $inicioDoMes->toDateString(),
                $fimDoMes->toDateString(),
            ])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (Movimentacao $movimentacao): array {
                return [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo,
                    'descricao' => $movimentacao->descricao,
                    'valor' => (float) $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' =>
                        $movimentacao->forma_pagamento,
                    'status' => $movimentacao->status,
                ];
            })
            ->values();

        return response()->json([
            'filtros' => [
                'mes' => $mesSelecionado,
            ],
            'resumo' => [
                'saldo_anterior' =>
                    $saldoAcumulado['saldo_inicial'],

                'entradas' =>
                    $saldoAcumulado['entradas'],

                'despesas' =>
                    $saldoAcumulado['despesas'],

                'total_disponivel' =>
                    $saldoAcumulado['total_disponivel'],

                'saldo' =>
                    $saldoAcumulado['saldo_final'],
                ],
                'ultimas_movimentacoes' => $ultimasMovimentacoes,
                ]);
    }
}
