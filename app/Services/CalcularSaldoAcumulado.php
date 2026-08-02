<?php

namespace App\Services;

use App\Models\Movimentacao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CalcularSaldoAcumulado
{
    /**
     * Calcula o saldo acumulado de um usuário para determinado período.
     *
     * O saldo inicial considera todas as movimentações anteriores
     * ao primeiro dia do período.
     *
     * @param callable(Builder): void|null $aplicarFiltros
     *
     * @return array{
     *     saldo_inicial: float,
     *     entradas: float,
     *     despesas: float,
     *     total_disponivel: float,
     *     saldo_final: float
     * }
     */
    public function calcular(
        int $userId,
        Carbon $inicio,
        Carbon $fim,
        ?callable $aplicarFiltros = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÕES ANTERIORES AO PERÍODO
        |--------------------------------------------------------------------------
        | A própria data inicial não entra no saldo anterior.
        | Ela pertence às movimentações do período selecionado.
        */
        $consultaAnterior = Movimentacao::query()
            ->where('user_id', $userId)
            ->whereDate(
                'data',
                '<',
                $inicio->toDateString()
            );

        if ($aplicarFiltros !== null) {
            $aplicarFiltros($consultaAnterior);
        }

        $entradasAnteriores = round(
            (float) (clone $consultaAnterior)
                ->where('tipo', 'entrada')
                ->sum('valor'),
            2
        );

        $despesasAnteriores = round(
            (float) (clone $consultaAnterior)
                ->where('tipo', 'despesa')
                ->sum('valor'),
            2
        );

        $saldoInicial = round(
            $entradasAnteriores - $despesasAnteriores,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÕES DENTRO DO PERÍODO
        |--------------------------------------------------------------------------
        */
        $consultaPeriodo = Movimentacao::query()
            ->where('user_id', $userId)
            ->whereDate(
                'data',
                '>=',
                $inicio->toDateString()
            )
            ->whereDate(
                'data',
                '<=',
                $fim->toDateString()
            );

        if ($aplicarFiltros !== null) {
            $aplicarFiltros($consultaPeriodo);
        }

        $totalEntradas = round(
            (float) (clone $consultaPeriodo)
                ->where('tipo', 'entrada')
                ->sum('valor'),
            2
        );

        $totalDespesas = round(
            (float) (clone $consultaPeriodo)
                ->where('tipo', 'despesa')
                ->sum('valor'),
            2
        );

        $totalDisponivel = round(
            $saldoInicial + $totalEntradas,
            2
        );

        $saldoFinal = round(
            $totalDisponivel - $totalDespesas,
            2
        );

        return [
            'saldo_inicial' => $saldoInicial,
            'entradas' => $totalEntradas,
            'despesas' => $totalDespesas,
            'total_disponivel' => $totalDisponivel,
            'saldo_final' => $saldoFinal,
        ];
    }
}
