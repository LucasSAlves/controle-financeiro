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

        /*
        |--------------------------------------------------------------------------
        | REGRAS VÁLIDAS PARA A COMPETÊNCIA
        |--------------------------------------------------------------------------
        | A regra pode estar inativa atualmente e ainda assim pertencer
        | corretamente a um mês anterior à sua data de encerramento.
        */
        $query = DespesaFixa::query()
            ->whereDate('data_inicio', '<=', $fimDoMes)
            ->where(function ($query) use ($inicioDoMes) {
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
                    |--------------------------------------------------------------------------
                    | TODAS AS VERSÕES DA MESMA RECORRÊNCIA
                    |--------------------------------------------------------------------------
                    | Uma despesa fixa pode possuir várias regras porque o usuário
                    | alterou "este mês e os próximos".
                    */
                    $queryRegrasDoGrupo = DespesaFixa::where(
                        'user_id',
                        $despesaFixa->user_id
                    );

                    if ($despesaFixa->grupo_recorrencia) {
                        $queryRegrasDoGrupo->where(
                            'grupo_recorrencia',
                            $despesaFixa->grupo_recorrencia
                        );
                    } else {
                        /*
                         * Proteção para alguma regra antiga que ainda esteja
                         * sem grupo de recorrência.
                         */
                        $queryRegrasDoGrupo->where(
                            'id',
                            $despesaFixa->id
                        );
                    }

                    $idsRegrasDoGrupo = $queryRegrasDoGrupo
                        ->pluck('id');

                    /*
                    |--------------------------------------------------------------------------
                    | ESCOLHER SOMENTE UMA REGRA PARA O MÊS
                    |--------------------------------------------------------------------------
                    | Caso duas versões estejam válidas por erro ou sobreposição,
                    | usa a versão mais recente e ignora as anteriores.
                    */
                    $regraValidaMaisRecente = DespesaFixa::whereIn(
                        'id',
                        $idsRegrasDoGrupo
                    )
                        ->whereDate(
                            'data_inicio',
                            '<=',
                            $fimDoMes
                        )
                        ->where(function ($query) use ($inicioDoMes) {
                            $query
                                ->whereNull('encerrada_em')
                                ->orWhereDate(
                                    'encerrada_em',
                                    '>',
                                    $inicioDoMes
                                );
                        })
                        ->orderByDesc('data_inicio')
                        ->orderByDesc('id')
                        ->first();

                    /*
                     * Somente a regra mais recente da recorrência
                     * pode gerar o lançamento deste mês.
                     */
                    if (
                        !$regraValidaMaisRecente ||
                        $regraValidaMaisRecente->id !== $despesaFixa->id
                    ) {
                        continue;
                    }

                    $competenciaFormatada = $inicioDoMes
                        ->copy()
                        ->startOfMonth()
                        ->format('Y-m-d');

                    /*
                    |--------------------------------------------------------------------------
                    | EXCEÇÕES DA RECORRÊNCIA
                    |--------------------------------------------------------------------------
                    | A exclusão de um mês deve valer para todas as versões
                    | da mesma recorrência.
                    */
                    $mesFoiIgnorado = DespesaFixaExcecao::whereIn(
                        'despesa_fixa_id',
                        $idsRegrasDoGrupo
                    )
                        ->whereDate(
                            'competencia',
                            $competenciaFormatada
                        )
                        ->exists();

                    if ($mesFoiIgnorado) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | MOVIMENTAÇÃO JÁ EXISTENTE NO GRUPO
                    |--------------------------------------------------------------------------
                    | Verifica todas as versões, e não somente o ID da regra atual.
                    | Assim, uma nova versão não duplica um lançamento já existente.
                    */
                    $jaExisteNoMes = Movimentacao::where(
                        'user_id',
                        $despesaFixa->user_id
                    )
                        ->whereIn(
                            'despesa_fixa_id',
                            $idsRegrasDoGrupo
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
                     * são ajustadas para o último dia de meses menores.
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
                            'forma_pagamento' =>
                                $despesaFixa->forma_pagamento,
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
