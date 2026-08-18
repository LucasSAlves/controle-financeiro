<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MovimentacaoIndexRequest;
use App\Http\Requests\Api\V1\MovimentacaoStoreRequest;
use App\Models\DespesaFixa;
use App\Models\EntradaFixa;
use App\Models\Movimentacao;
use App\Services\GerarDespesasFixasMensais;
use App\Services\GerarEntradasFixasMensais;
use App\Services\GerarMovimentacoesFixasAteCompetencia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        /**
     * Cadastra uma nova movimentação pelo aplicativo.
     */
    public function store(
        MovimentacaoStoreRequest $request,
        GerarDespesasFixasMensais $geradorDespesasFixas,
        GerarEntradasFixasMensais $geradorEntradasFixas
    ): JsonResponse {
        $user = $request->user();
        $dados = $request->validated();

        $parcelado = $request->boolean('parcelado');
        $parcelaFixa = $request->boolean('parcela_fixa');
        $fixoMensal = $request->boolean('fixo_mensal');

        /*
         * ENTRADA FIXA MENSAL
         */
        if (
            $dados['tipo'] === 'entrada'
            && $fixoMensal
        ) {
            DB::transaction(function () use (
                $dados,
                $user,
                $geradorEntradasFixas
            ): void {
                $dataInicio = Carbon::parse(
                    $dados['data']
                );

                EntradaFixa::create([
                    'user_id' => $user->id,
                    'descricao' => $dados['descricao'],
                    'valor' => $dados['valor'],
                    'data_inicio' =>
                        $dataInicio->format('Y-m-d'),
                    'dia_recebimento' =>
                        $dataInicio->day,
                    'categoria' =>
                        $dados['categoria'] ?? null,
                    'observacao' =>
                        $dados['observacao'] ?? null,
                    'ativa' => true,
                    'encerrada_em' => null,
                ]);

                $geradorEntradasFixas->gerarParaMes(
                    $dataInicio,
                    (int) $user->id
                );
            });

            return response()->json([
                'message' =>
                    'Entrada fixa mensal cadastrada com sucesso.',
            ], 201);
        }

        /*
         * DESPESA FIXA MENSAL
         */
        if (
            $dados['tipo'] === 'despesa'
            && $parcelaFixa
        ) {
            DB::transaction(function () use (
                $dados,
                $user,
                $geradorDespesasFixas
            ): void {
                $dataInicio = Carbon::parse(
                    $dados['data']
                );

                DespesaFixa::create([
                    'user_id' => $user->id,
                    'descricao' => $dados['descricao'],
                    'valor' => $dados['valor'],
                    'data_inicio' =>
                        $dataInicio->format('Y-m-d'),
                    'dia_vencimento' =>
                        $dataInicio->day,
                    'categoria' =>
                        $dados['categoria'] ?? null,
                    'forma_pagamento' =>
                        $dados['forma_pagamento'] ?? null,
                    'observacao' =>
                        $dados['observacao'] ?? null,
                    'ativa' => true,
                    'encerrada_em' => null,
                ]);

                $geradorDespesasFixas->gerarParaMes(
                    $dataInicio,
                    (int) $user->id
                );
            });

            return response()->json([
                'message' =>
                    'Despesa fixa mensal cadastrada com sucesso.',
            ], 201);
        }

        /*
         * MOVIMENTAÇÃO NORMAL
         */
        if (! $parcelado) {
            $dataPagamento = null;

            if (
                $dados['tipo'] === 'despesa'
                && $dados['status'] === 'pago'
            ) {
                $dataPagamento = now()->toDateString();
            }

            $movimentacao = Movimentacao::create([
                'user_id' => $user->id,
                'tipo' => $dados['tipo'],
                'descricao' => $dados['descricao'],
                'valor' => $dados['valor'],
                'data' => $dados['data'],

                'categoria' =>
                    $dados['categoria'] ?? null,

                'forma_pagamento' =>
                    $dados['tipo'] === 'entrada'
                        ? null
                        : ($dados['forma_pagamento'] ?? null),

                'status' =>
                    $dados['tipo'] === 'entrada'
                        ? 'recebido'
                        : $dados['status'],

                'observacao' =>
                    $dados['observacao'] ?? null,

                'parcelado' => false,
                'parcela_fixa' => false,
                'fixo_mensal' => false,

                'mes_atual' => null,
                'total_meses' => null,

                'parcela_atual' => null,
                'total_parcelas' => null,

                'grupo_parcelamento' => null,
                'grupo_fixo_mensal' => null,

                'data_pagamento' => $dataPagamento,
            ]);

            return response()->json([
                'message' =>
                    'Movimentação cadastrada com sucesso.',

                'movimentacao' => [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo,
                    'descricao' =>
                        $movimentacao->descricao,
                    'valor' =>
                        (float) $movimentacao->valor,
                    'data' =>
                        $movimentacao->data
                            ->format('Y-m-d'),

                    'data_pagamento' =>
                        $movimentacao->data_pagamento
                            ? $movimentacao
                                ->data_pagamento
                                ->format('Y-m-d')
                            : null,

                    'categoria' =>
                        $movimentacao->categoria,

                    'forma_pagamento' =>
                        $movimentacao->forma_pagamento,

                    'status' =>
                        $movimentacao->status,

                    'observacao' =>
                        $movimentacao->observacao,
                ],
            ], 201);
        }

        /*
         * DESPESA PARCELADA
         *
         * O valor informado corresponde ao valor
         * de cada parcela.
         */
        $totalParcelas =
            (int) $dados['total_parcelas'];

        $grupoParcelamento =
            (string) Str::uuid();

        $valorParcela =
            (float) $dados['valor'];

        $dataPrimeiraParcela =
            Carbon::parse($dados['data']);

        $parcelasCriadas = [];

        DB::transaction(function () use (
            $dados,
            $user,
            $totalParcelas,
            $grupoParcelamento,
            $valorParcela,
            $dataPrimeiraParcela,
            &$parcelasCriadas
        ): void {
            for (
                $parcela = 1;
                $parcela <= $totalParcelas;
                $parcela++
            ) {
                $descricaoParcela =
                    $dados['descricao']
                    . ' - Parcela '
                    . $parcela
                    . '/'
                    . $totalParcelas;

                $movimentacao = Movimentacao::create([
                    'user_id' => $user->id,
                    'tipo' => 'despesa',
                    'descricao' => $descricaoParcela,
                    'valor' => $valorParcela,

                    'data' => $dataPrimeiraParcela
                        ->copy()
                        ->addMonthsNoOverflow(
                            $parcela - 1
                        )
                        ->format('Y-m-d'),

                    'categoria' =>
                        $dados['categoria'] ?? null,

                    'forma_pagamento' =>
                        $dados['forma_pagamento'] ?? null,

                    'status' => 'pendente',

                    'observacao' =>
                        $dados['observacao'] ?? null,

                    'parcelado' => true,
                    'parcela_fixa' => false,
                    'fixo_mensal' => false,

                    'mes_atual' => null,
                    'total_meses' => null,

                    'parcela_atual' => $parcela,
                    'total_parcelas' =>
                        $totalParcelas,

                    'grupo_parcelamento' =>
                        $grupoParcelamento,

                    'grupo_fixo_mensal' => null,

                    'data_pagamento' => null,
                ]);

                $parcelasCriadas[] = [
                    'id' =>
                        $movimentacao->id,

                    'parcela_atual' =>
                        $parcela,

                    'total_parcelas' =>
                        $totalParcelas,

                    'descricao' =>
                        $movimentacao->descricao,

                    'valor' =>
                        (float) $movimentacao->valor,

                    'data' =>
                        $movimentacao->data
                            ->format('Y-m-d'),
                ];
            }
        });

        return response()->json([
            'message' =>
                'Compra parcelada cadastrada com sucesso.',

            'grupo_parcelamento' =>
                $grupoParcelamento,

            'parcelas' =>
                $parcelasCriadas,
        ], 201);
    }
    /**
 * Retorna os detalhes de uma movimentação
 * pertencente ao usuário autenticado.
 */
public function show(
    string $id
): JsonResponse {
    $user = request()->user();

    $movimentacao = Movimentacao::query()
        ->where('user_id', $user->id)
        ->where('id', $id)
        ->firstOrFail();

    return response()->json([
        'movimentacao' => [
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

            'categoria' =>
                $movimentacao->categoria,

            'forma_pagamento' =>
                $movimentacao->forma_pagamento,

            'status' =>
                $movimentacao->status,

            'observacao' =>
                $movimentacao->observacao,

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
        ],
    ]);
}

public function update(
    Request $request,
    string $id
): JsonResponse {
    $movimentacao = Movimentacao::where(
        'user_id',
        $request->user()->id
    )
        ->where('id', $id)
        ->firstOrFail();

    /*
    |--------------------------------------------------------------------------
    | Despesas e entradas fixas serão implementadas separadamente.
    |--------------------------------------------------------------------------
    */
    if (
        $movimentacao->parcela_fixa
        || $movimentacao->fixo_mensal
    ) {
        return response()->json([
            'message' =>
                'Esta movimentacao possui regras especiais de edicao.',
        ], 422);
    }

    $dados = $request->validate([
        'tipo' => [
            'required',
            'in:entrada,despesa',
        ],
        'descricao' => [
            'required',
            'string',
            'max:255',
        ],
        'valor' => [
            'required',
            'numeric',
            'min:0.01',
        ],
        'data' => [
            'required',
            'date',
        ],
        'categoria' =>
            $request->input('tipo') === 'despesa'
                ? [
                    'required',
                    'string',
                    'max:255',
                ]
                : [
                    'nullable',
                    'string',
                    'max:255',
                ],
        'forma_pagamento' =>
            $request->input('tipo') === 'despesa'
                ? [
                    'required',
                    'string',
                    'max:255',
                ]
                : [
                    'nullable',
                    'string',
                    'max:255',
                ],
        'status' => [
            'required',
            'in:pago,pendente,recebido',
        ],
        'observacao' => [
            'nullable',
            'string',
        ],
        'total_parcelas' => [
            'nullable',
            'integer',
            'min:2',
            'max:120',
        ],
    ]);

    /*
    |--------------------------------------------------------------------------
    | DESPESA PARCELADA
    |--------------------------------------------------------------------------
    */
    if ($movimentacao->parcelado) {
        if ($dados['tipo'] !== 'despesa') {
            return response()->json([
                'message' =>
                    'Uma despesa parcelada nao pode ser transformada em entrada.',
            ], 422);
        }

        if (!$movimentacao->grupo_parcelamento) {
            return response()->json([
                'message' =>
                    'O grupo deste parcelamento nao foi encontrado.',
            ], 422);
        }

        if (empty($dados['total_parcelas'])) {
            return response()->json([
                'message' =>
                    'Informe a quantidade de parcelas.',
            ], 422);
        }

        $novoTotalParcelas =
            (int) $dados['total_parcelas'];

        $parcelasDoGrupo =
            Movimentacao::where(
                'user_id',
                $request->user()->id
            )
                ->where(
                    'grupo_parcelamento',
                    $movimentacao->grupo_parcelamento
                )
                ->orderBy('parcela_atual')
                ->get();

        if ($parcelasDoGrupo->isEmpty()) {
            return response()->json([
                'message' =>
                    'Nenhuma parcela deste grupo foi encontrada.',
            ], 422);
        }

        $totalParcelasAtual = (int) (
            $parcelasDoGrupo->max(
                'total_parcelas'
            )
            ?: $movimentacao->total_parcelas
            ?: $parcelasDoGrupo->max(
                'parcela_atual'
            )
        );

        /*
        * Antes de reduzir, verifica se alguma parcela
        * que seria removida já está paga.
        */
        $parcelasPagasExcedentes =
            $parcelasDoGrupo->filter(
                function ($item) use (
                    $novoTotalParcelas
                ) {
                    return (
                        (int) $item->parcela_atual
                            > $novoTotalParcelas
                        && (
                            $item->status === 'pago'
                            || $item->data_pagamento
                        )
                    );
                }
            );

        if (
            $parcelasPagasExcedentes
                ->isNotEmpty()
        ) {
            $numerosParcelas =
                $parcelasPagasExcedentes
                    ->pluck('parcela_atual')
                    ->implode(', ');

            return response()->json([
                'message' =>
                    "Nao e possivel reduzir para {$novoTotalParcelas} parcelas, pois a(s) parcela(s) {$numerosParcelas} ja esta(ao) paga(s).",
            ], 422);
        }

        /*
        * Retira do texto o sufixo:
        * "- Parcela 2/4"
        */
        $descricaoBase = trim(
            (string) preg_replace(
                '/\s*-\s*Parcela\s+\d+\/\d+\s*$/i',
                '',
                $dados['descricao']
            )
        );

        $numeroParcelaSelecionada =
            (int) (
                $movimentacao->parcela_atual
                ?? 1
            );

        $parcelaSelecionadaSeraRemovida =
            $numeroParcelaSelecionada
                > $novoTotalParcelas;

        DB::transaction(
            function () use (
                $request,
                $dados,
                $movimentacao,
                $descricaoBase,
                $novoTotalParcelas,
                $totalParcelasAtual,
                $numeroParcelaSelecionada,
                $parcelaSelecionadaSeraRemovida
            ) {
                /*
                * REDUZIR QUANTIDADE
                *
                * Remove somente parcelas excedentes
                * que ainda estejam pendentes.
                */
                if (
                    $novoTotalParcelas
                        < $totalParcelasAtual
                ) {
                    Movimentacao::where(
                        'user_id',
                        $request->user()->id
                    )
                        ->where(
                            'grupo_parcelamento',
                            $movimentacao
                                ->grupo_parcelamento
                        )
                        ->where(
                            'parcela_atual',
                            '>',
                            $novoTotalParcelas
                        )
                        ->where(
                            'status',
                            'pendente'
                        )
                        ->whereNull(
                            'data_pagamento'
                        )
                        ->delete();
                }

                /*
                * Edita somente a parcela que foi aberta
                * pelo usuário.
                */
                if (
                    !$parcelaSelecionadaSeraRemovida
                ) {
                    $movimentacao->refresh();

                    $status =
                        $dados['status'] === 'pago'
                            ? 'pago'
                            : 'pendente';

                    $movimentacao->update([
                        'tipo' => 'despesa',

                        'descricao' =>
                            $descricaoBase
                            . ' - Parcela '
                            . $numeroParcelaSelecionada
                            . '/'
                            . $novoTotalParcelas,

                        'valor' =>
                            $dados['valor'],

                        'data' =>
                            $dados['data'],

                        'categoria' =>
                            $dados['categoria']
                            ?? null,

                        'forma_pagamento' =>
                            $dados[
                                'forma_pagamento'
                            ]
                            ?? null,

                        'status' =>
                            $status,

                        'observacao' =>
                            $dados['observacao']
                            ?? null,

                        'total_parcelas' =>
                            $novoTotalParcelas,

                        'data_pagamento' =>
                            $status === 'pago'
                                ? (
                                    $movimentacao
                                        ->data_pagamento
                                        ? $movimentacao
                                            ->data_pagamento
                                            ->format(
                                                'Y-m-d'
                                            )
                                        : now()
                                            ->toDateString()
                                )
                                : null,
                    ]);
                }

                /*
                * AUMENTAR QUANTIDADE
                *
                * Cria somente as novas parcelas
                * no final do grupo.
                */
                if (
                    $novoTotalParcelas
                        > $totalParcelasAtual
                ) {
                    $ultimaParcelaExistente =
                        Movimentacao::where(
                            'user_id',
                            $request->user()->id
                        )
                            ->where(
                                'grupo_parcelamento',
                                $movimentacao
                                    ->grupo_parcelamento
                            )
                            ->orderByDesc(
                                'parcela_atual'
                            )
                            ->first();

                    if (
                        !$ultimaParcelaExistente
                    ) {
                        throw new \RuntimeException(
                            'Nao foi possivel encontrar a ultima parcela.'
                        );
                    }

                    for (
                        $parcela =
                            $totalParcelasAtual + 1;
                        $parcela
                            <= $novoTotalParcelas;
                        $parcela++
                    ) {
                        $diferencaParcelas =
                            $parcela
                            - (int)
                                $ultimaParcelaExistente
                                    ->parcela_atual;

                        $dataNovaParcela =
                            $ultimaParcelaExistente
                                ->data
                                ->copy()
                                ->addMonthsNoOverflow(
                                    $diferencaParcelas
                                );

                        Movimentacao::firstOrCreate(
                            [
                                'user_id' =>
                                    $request
                                        ->user()
                                        ->id,

                                'grupo_parcelamento' =>
                                    $movimentacao
                                        ->grupo_parcelamento,

                                'parcela_atual' =>
                                    $parcela,
                            ],
                            [
                                'tipo' =>
                                    'despesa',

                                'descricao' =>
                                    $descricaoBase
                                    . ' - Parcela '
                                    . $parcela
                                    . '/'
                                    . $novoTotalParcelas,

                                'valor' =>
                                    $dados['valor'],

                                'data' =>
                                    $dataNovaParcela
                                        ->format(
                                            'Y-m-d'
                                        ),

                                'categoria' =>
                                    $dados[
                                        'categoria'
                                    ]
                                    ?? null,

                                'forma_pagamento' =>
                                    $dados[
                                        'forma_pagamento'
                                    ]
                                    ?? null,

                                'status' =>
                                    'pendente',

                                'observacao' =>
                                    $dados[
                                        'observacao'
                                    ]
                                    ?? null,

                                'parcelado' =>
                                    true,

                                'parcela_fixa' =>
                                    false,

                                'fixo_mensal' =>
                                    false,

                                'mes_atual' =>
                                    null,

                                'total_meses' =>
                                    null,

                                'total_parcelas' =>
                                    $novoTotalParcelas,

                                'grupo_fixo_mensal' =>
                                    null,

                                'despesa_fixa_id' =>
                                    null,

                                'entrada_fixa_id' =>
                                    null,

                                'data_pagamento' =>
                                    null,

                                'aviso_vencimento_enviado_em' =>
                                    null,
                            ]
                        );
                    }
                }

                /*
                * Atualiza a identificação de todas
                * as parcelas restantes:
                *
                * Parcela 1/4
                * Parcela 2/4
                * Parcela 3/4...
                */
                $parcelasAtualizadas =
                    Movimentacao::where(
                        'user_id',
                        $request->user()->id
                    )
                        ->where(
                            'grupo_parcelamento',
                            $movimentacao
                                ->grupo_parcelamento
                        )
                        ->orderBy(
                            'parcela_atual'
                        )
                        ->get();

                foreach (
                    $parcelasAtualizadas
                    as $item
                ) {
                    $numeroParcela =
                        (int)
                            $item
                                ->parcela_atual;

                    $item->update([
                        'descricao' =>
                            $descricaoBase
                            . ' - Parcela '
                            . $numeroParcela
                            . '/'
                            . $novoTotalParcelas,

                        'total_parcelas' =>
                            $novoTotalParcelas,
                    ]);
                }
            }
        );

        $movimentacaoAtualizada =
            Movimentacao::where(
                'user_id',
                $request->user()->id
            )
                ->where(
                    'id',
                    $movimentacao->id
                )
                ->first();

        $mensagem =
            $novoTotalParcelas
                === $totalParcelasAtual
                ? 'Parcela atualizada com sucesso.'
                : "Parcelamento atualizado de {$totalParcelasAtual} para {$novoTotalParcelas} parcelas.";

        return response()->json([
            'message' => $mensagem,

            'movimentacao' =>
                $movimentacaoAtualizada
                    ? [
                        'id' =>
                            $movimentacaoAtualizada
                                ->id,

                        'tipo' =>
                            $movimentacaoAtualizada
                                ->tipo,

                        'descricao' =>
                            $movimentacaoAtualizada
                                ->descricao,

                        'valor' =>
                            $movimentacaoAtualizada
                                ->valor,

                        'data' =>
                            $movimentacaoAtualizada
                                ->data
                                ->format(
                                    'Y-m-d'
                                ),

                        'data_pagamento' =>
                            $movimentacaoAtualizada
                                ->data_pagamento
                                ? $movimentacaoAtualizada
                                    ->data_pagamento
                                    ->format(
                                        'Y-m-d'
                                    )
                                : null,

                        'categoria' =>
                            $movimentacaoAtualizada
                                ->categoria,

                        'forma_pagamento' =>
                            $movimentacaoAtualizada
                                ->forma_pagamento,

                        'status' =>
                            $movimentacaoAtualizada
                                ->status,

                        'observacao' =>
                            $movimentacaoAtualizada
                                ->observacao,

                        'parcelado' =>
                            $movimentacaoAtualizada
                                ->parcelado,

                        'parcela_fixa' =>
                            $movimentacaoAtualizada
                                ->parcela_fixa,

                        'fixo_mensal' =>
                            $movimentacaoAtualizada
                                ->fixo_mensal,

                        'despesa_fixa_id' =>
                            $movimentacaoAtualizada
                                ->despesa_fixa_id,

                        'entrada_fixa_id' =>
                            $movimentacaoAtualizada
                                ->entrada_fixa_id,

                        'parcela_atual' =>
                            $movimentacaoAtualizada
                                ->parcela_atual,

                        'total_parcelas' =>
                            $movimentacaoAtualizada
                                ->total_parcelas,

                        'grupo_parcelamento' =>
                            $movimentacaoAtualizada
                                ->grupo_parcelamento,

                        'mes_atual' =>
                            $movimentacaoAtualizada
                                ->mes_atual,

                        'total_meses' =>
                            $movimentacaoAtualizada
                                ->total_meses,

                        'grupo_fixo_mensal' =>
                            $movimentacaoAtualizada
                                ->grupo_fixo_mensal,
                    ]
                    : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MOVIMENTAÇÃO NORMAL
    |--------------------------------------------------------------------------
    */
    if ($dados['tipo'] === 'despesa') {
        $dados['status'] =
            $dados['status'] === 'pago'
                ? 'pago'
                : 'pendente';

        if ($dados['status'] === 'pago') {
            $dados['data_pagamento'] =
                $movimentacao->data_pagamento
                    ? $movimentacao
                        ->data_pagamento
                        ->format('Y-m-d')
                    : now()->toDateString();
        } else {
            $dados['data_pagamento'] =
                null;
        }
    }

    if ($dados['tipo'] === 'entrada') {
        $dados['status'] = 'recebido';
        $dados['forma_pagamento'] = null;
        $dados['data_pagamento'] = null;
    }

    unset(
        $dados['total_parcelas']
    );

    $movimentacao->update($dados);
    $movimentacao->refresh();

    return response()->json([
        'message' =>
            'Movimentacao atualizada com sucesso.',

        'movimentacao' => [
            'id' =>
                $movimentacao->id,

            'tipo' =>
                $movimentacao->tipo,

            'descricao' =>
                $movimentacao->descricao,

            'valor' =>
                $movimentacao->valor,

            'data' =>
                $movimentacao->data
                    ->format('Y-m-d'),

            'data_pagamento' =>
                $movimentacao->data_pagamento
                    ? $movimentacao
                        ->data_pagamento
                        ->format('Y-m-d')
                    : null,

            'categoria' =>
                $movimentacao->categoria,

            'forma_pagamento' =>
                $movimentacao
                    ->forma_pagamento,

            'status' =>
                $movimentacao->status,

            'observacao' =>
                $movimentacao->observacao,

            'parcelado' =>
                $movimentacao->parcelado,

            'parcela_fixa' =>
                $movimentacao->parcela_fixa,

            'fixo_mensal' =>
                $movimentacao->fixo_mensal,

            'despesa_fixa_id' =>
                $movimentacao
                    ->despesa_fixa_id,

            'entrada_fixa_id' =>
                $movimentacao
                    ->entrada_fixa_id,

            'parcela_atual' =>
                $movimentacao
                    ->parcela_atual,

            'total_parcelas' =>
                $movimentacao
                    ->total_parcelas,

            'grupo_parcelamento' =>
                $movimentacao
                    ->grupo_parcelamento,

            'mes_atual' =>
                $movimentacao->mes_atual,

            'total_meses' =>
                $movimentacao->total_meses,

            'grupo_fixo_mensal' =>
                $movimentacao
                    ->grupo_fixo_mensal,
        ],
    ]);
}

    /**
     * Marca uma despesa como paga.
     */
    public function marcarComoPago( string $id): JsonResponse {
        $user = request()->user();

        $movimentacao = Movimentacao::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($movimentacao->tipo !== 'despesa') {
            return response()->json([
                'message' =>
                    'Somente despesas podem ser marcadas como pagas.',
            ], 422);
        }

        $movimentacao->update([
            'status' => 'pago',
            'data_pagamento' => now()->toDateString(),
        ]);

        $movimentacao->refresh();

        return response()->json([
            'message' =>
                'Despesa marcada como paga com sucesso.',

            'movimentacao' => [
                'id' => $movimentacao->id,
                'status' => $movimentacao->status,

                'data_pagamento' =>
                    $movimentacao->data_pagamento
                        ? $movimentacao->data_pagamento
                            ->format('Y-m-d')
                        : null,
            ],
        ]);
    }

    /**
     * Exclui uma movimentação do usuário autenticado.
     */
    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $movimentacao = Movimentacao::where(
            'user_id',
            $request->user()->id
        )
            ->where('id', $id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Fixas ainda serão implementadas separadamente.
        |--------------------------------------------------------------------------
        */
        if (
            $movimentacao->parcela_fixa
            || $movimentacao->fixo_mensal
        ) {
            return response()->json([
                'message' =>
                    'Esta movimentacao possui regras especiais de exclusao.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | DESPESA PARCELADA
        |--------------------------------------------------------------------------
        */
        if ($movimentacao->parcelado) {
            $modoExclusao =
                $request->input(
                    'modo_exclusao',
                    'atual'
                );

            if (
                !in_array(
                    $modoExclusao,
                    [
                        'atual',
                        'futuras',
                    ],
                    true
                )
            ) {
                $modoExclusao = 'atual';
            }

            /*
            * Exclui a parcela selecionada e todas
            * as próximas que ainda estão pendentes.
            *
            * Parcelas pagas são preservadas.
            */
            if ($modoExclusao === 'futuras') {
                $quantidadeExcluida =
                    Movimentacao::where(
                        'user_id',
                        $request->user()->id
                    )
                        ->where(
                            'grupo_parcelamento',
                            $movimentacao
                                ->grupo_parcelamento
                        )
                        ->where(
                            'parcela_atual',
                            '>=',
                            $movimentacao
                                ->parcela_atual
                        )
                        ->where(
                            'status',
                            'pendente'
                        )
                        ->whereNull(
                            'data_pagamento'
                        )
                        ->delete();

                if ($quantidadeExcluida === 0) {
                    return response()->json([
                        'message' =>
                            'Nenhuma parcela foi excluida. Parcelas pagas nao podem ser apagadas.',
                    ], 422);
                }

                return response()->json([
                    'message' =>
                        "{$quantidadeExcluida} parcela(s) excluida(s) com sucesso. Parcelas pagas foram mantidas.",
                ]);
            }

            /*
            * Excluir somente a parcela atual.
            */
            if (
                $movimentacao->status === 'pago'
                || $movimentacao->data_pagamento
            ) {
                return response()->json([
                    'message' =>
                        'Esta parcela ja foi paga e nao pode ser excluida.',
                ], 422);
            }

            $movimentacao->delete();

            return response()->json([
                'message' =>
                    'Parcela excluida com sucesso.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÃO NORMAL
        |--------------------------------------------------------------------------
        */
        $movimentacao->delete();

        return response()->json([
            'message' =>
                'Movimentacao excluida com sucesso.',
        ]);
    }
}
