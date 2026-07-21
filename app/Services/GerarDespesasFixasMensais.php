<?php

namespace App\Services;

use App\Models\DespesaFixa;
use App\Models\DespesaFixaExcecao;
use App\Models\Movimentacao;
use Carbon\Carbon;

class GerarDespesasFixasMensais
{
    public function gerarParaMes(
        Carbon|string $mes,
        ?int $userId = null
    ): int {
        $competencia = $mes instanceof Carbon
            ? $mes->copy()->startOfMonth()
            : Carbon::parse($mes)->startOfMonth();

        $inicioDoMes = $competencia->copy()->startOfMonth();
        $fimDoMes = $competencia->copy()->endOfMonth();

        $query = DespesaFixa::query()
            ->whereDate('data_inicio', '<=', $fimDoMes)
            ->where(function ($query) use ($inicioDoMes) {
                /*
                * Uma despesa encerrada ainda pertence aos meses anteriores
                * à data de encerramento.
                */
                $query
                    ->whereNull('encerrada_em')
                    ->orWhereDate('encerrada_em', '>', $inicioDoMes);
            });
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $quantidadeCriada = 0;

        $query->chunkById(
            100,
            function ($despesasFixas) use (
                $inicioDoMes,
                $fimDoMes,
                &$quantidadeCriada
            ) {
                foreach ($despesasFixas as $despesaFixa) {
                    /*
                    * Não gera a despesa quando o usuário excluiu
                    * somente o lançamento deste mês.
                    */
                    $competencia = $inicioDoMes
                        ->copy()
                        ->startOfMonth()
                        ->format('Y-m-d');

                    $mesFoiIgnorado = DespesaFixaExcecao::where(
                        'despesa_fixa_id',
                        $despesaFixa->id
                    )
                        ->whereDate('competencia', $competencia)
                        ->exists();

                    if ($mesFoiIgnorado) {
                        continue;
                    }
                    /*
                     * Evita criar novamente uma movimentação fixa
                     * que já existe naquele mês.
                     */
                    $jaExisteNoMes = Movimentacao::where(
                        'despesa_fixa_id',
                        $despesaFixa->id
                    )
                        ->whereBetween('data', [
                            $inicioDoMes,
                            $fimDoMes,
                        ])
                        ->exists();

                    if ($jaExisteNoMes) {
                        continue;
                    }

                    /*
                     * Contas com vencimento nos dias 29, 30 ou 31
                     * serão ajustadas para o último dia de meses menores.
                     */
                    $diaVencimento = min(
                        $despesaFixa->dia_vencimento,
                        $inicioDoMes->daysInMonth
                    );

                    $dataVencimento = $inicioDoMes
                        ->copy()
                        ->day($diaVencimento);

                    $movimentacao = Movimentacao::firstOrCreate(
                        [
                            'despesa_fixa_id' => $despesaFixa->id,
                            'data' => $dataVencimento->format('Y-m-d'),
                        ],
                        [
                            'user_id' => $despesaFixa->user_id,
                            'tipo' => 'despesa',
                            'descricao' => $despesaFixa->descricao,
                            'valor' => $despesaFixa->valor,
                            'categoria' => $despesaFixa->categoria,
                            'forma_pagamento' => $despesaFixa->forma_pagamento,
                            'status' => 'pendente',
                            'observacao' => $despesaFixa->observacao,

                            'parcelado' => false,
                            'parcela_fixa' => true,
                            'fixo_mensal' => false,

                            'mes_atual' => null,
                            'total_meses' => null,
                            'parcela_atual' => null,
                            'total_parcelas' => null,

                            'grupo_parcelamento' => null,
                            'grupo_fixo_mensal' => null,

                            'data_pagamento' => null,
                            'aviso_vencimento_enviado_em' => null,
                        ]
                    );

                    if ($movimentacao->wasRecentlyCreated) {
                        $quantidadeCriada++;
                    }
                }
            }
        );

        return $quantidadeCriada;
    }
}
