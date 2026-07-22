<?php

namespace App\Services;

use App\Models\EntradaFixa;
use App\Models\EntradaFixaExcecao;
use App\Models\Movimentacao;
use Carbon\Carbon;

class GerarEntradasFixasMensais
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

        $query = EntradaFixa::query()
            ->whereDate('data_inicio', '<=', $fimDoMes)
            ->where(function ($query) use ($inicioDoMes) {
                /*
                 * Uma entrada encerrada ainda pertence aos meses
                 * anteriores à data de encerramento.
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
            function ($entradasFixas) use (
                $inicioDoMes,
                $fimDoMes,
                &$quantidadeCriada
            ) {
                foreach ($entradasFixas as $entradaFixa) {
                    /*
                     * Não gera a entrada quando o usuário excluiu
                     * somente o lançamento daquele mês.
                     */
                    $competencia = $inicioDoMes
                        ->copy()
                        ->startOfMonth()
                        ->format('Y-m-d');

                    $mesFoiIgnorado = EntradaFixaExcecao::where(
                        'entrada_fixa_id',
                        $entradaFixa->id
                    )
                        ->whereDate('competencia', $competencia)
                        ->exists();

                    if ($mesFoiIgnorado) {
                        continue;
                    }

                    /*
                     * Evita criar novamente uma entrada fixa
                     * que já existe no mês.
                     */
                    $jaExisteNoMes = Movimentacao::where(
                        'entrada_fixa_id',
                        $entradaFixa->id
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
                     * Datas nos dias 29, 30 ou 31 serão ajustadas
                     * para o último dia de meses menores.
                     */
                    $diaRecebimento = min(
                        $entradaFixa->dia_recebimento,
                        $inicioDoMes->daysInMonth
                    );

                    $dataRecebimento = $inicioDoMes
                        ->copy()
                        ->day($diaRecebimento);

                    $movimentacao = Movimentacao::firstOrCreate(
                        [
                            'entrada_fixa_id' => $entradaFixa->id,
                            'data' => $dataRecebimento->format('Y-m-d'),
                        ],
                        [
                            'user_id' => $entradaFixa->user_id,
                            'tipo' => 'entrada',
                            'descricao' => $entradaFixa->descricao,
                            'valor' => $entradaFixa->valor,
                            'categoria' => $entradaFixa->categoria,
                            'forma_pagamento' => null,
                            'status' => 'recebido',
                            'observacao' => $entradaFixa->observacao,

                            'parcelado' => false,
                            'parcela_fixa' => false,
                            'fixo_mensal' => true,

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
