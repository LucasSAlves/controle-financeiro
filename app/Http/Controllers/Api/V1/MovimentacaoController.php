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
    | Nesta primeira etapa, a API edita somente movimentações normais.
    |--------------------------------------------------------------------------
    | Parceladas e recorrentes terão regras específicas implementadas
    | separadamente para evitar alterações indevidas no grupo.
    */
    if (
        $movimentacao->parcelado
        || $movimentacao->parcela_fixa
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
        'categoria' => $request->input('tipo') === 'despesa'
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
    ]);

    if ($dados['tipo'] === 'despesa') {
        $dados['status'] = $dados['status'] === 'pago'
            ? 'pago'
            : 'pendente';

        if ($dados['status'] === 'pago') {
            $dados['data_pagamento'] =
                $movimentacao->data_pagamento
                    ? $movimentacao->data_pagamento->format('Y-m-d')
                    : now()->toDateString();
        } else {
            $dados['data_pagamento'] = null;
        }
    }

    if ($dados['tipo'] === 'entrada') {
        $dados['status'] = 'recebido';
        $dados['forma_pagamento'] = null;
        $dados['data_pagamento'] = null;
    }

    $movimentacao->update($dados);
    $movimentacao->refresh();

    return response()->json([
        'message' => 'Movimentacao atualizada com sucesso.',
        'movimentacao' => [
            'id' => $movimentacao->id,
            'tipo' => $movimentacao->tipo,
            'descricao' => $movimentacao->descricao,
            'valor' => $movimentacao->valor,
            'data' => $movimentacao->data->format('Y-m-d'),
            'data_pagamento' => $movimentacao->data_pagamento
                ? $movimentacao->data_pagamento->format('Y-m-d')
                : null,
            'categoria' => $movimentacao->categoria,
            'forma_pagamento' => $movimentacao->forma_pagamento,
            'status' => $movimentacao->status,
            'observacao' => $movimentacao->observacao,

            'parcelado' => $movimentacao->parcelado,
            'parcela_fixa' => $movimentacao->parcela_fixa,
            'fixo_mensal' => $movimentacao->fixo_mensal,

            'despesa_fixa_id' => $movimentacao->despesa_fixa_id,
            'entrada_fixa_id' => $movimentacao->entrada_fixa_id,

            'parcela_atual' => $movimentacao->parcela_atual,
            'total_parcelas' => $movimentacao->total_parcelas,
            'grupo_parcelamento' =>
                $movimentacao->grupo_parcelamento,

            'mes_atual' => $movimentacao->mes_atual,
            'total_meses' => $movimentacao->total_meses,
            'grupo_fixo_mensal' =>
                $movimentacao->grupo_fixo_mensal,
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
        | Nesta primeira etapa, exclui somente movimentações normais.
        |--------------------------------------------------------------------------
        */
        if (
            $movimentacao->parcelado
            || $movimentacao->parcela_fixa
            || $movimentacao->fixo_mensal
        ) {
            return response()->json([
                'message' =>
                    'Esta movimentacao possui regras especiais de exclusao.',
            ], 422);
        }

        $movimentacao->delete();

        return response()->json([
            'message' => 'Movimentacao excluida com sucesso.',
        ]);
    }
}
