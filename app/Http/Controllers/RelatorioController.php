<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Services\CalcularSaldoAcumulado;
use App\Services\GerarMovimentacoesFixasAteCompetencia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RelatorioController extends Controller
{
    public function index(
        Request $request,
        GerarMovimentacoesFixasAteCompetencia $geradorHistoricoFixo,
        CalcularSaldoAcumulado $calculadorSaldo
    ): Response {
        $user = $request->user();

        $periodo = $request->string('periodo', 'mes')->toString();
        $tipo = $request->string('tipo', 'geral')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array(
            $periodo,
            ['semana', 'mes', 'ano', 'personalizado'],
            true
        )) {
            $periodo = 'mes';
        }

        if (! in_array(
            $tipo,
            ['geral', 'entrada', 'despesa'],
            true
        )) {
            $tipo = 'geral';
        }

        if (! in_array(
            $status,
            ['todos', 'concluidos', 'pendentes'],
            true
        )) {
            $status = 'todos';
        }

        $referencia = $this->resolverReferencia(
            $request->input('referencia')
        );

        [$inicio, $fim] = $this->resolverIntervalo(
            $request,
            $periodo,
            $referencia
        );

        /*
        |--------------------------------------------------------------------------
        | LIMITE DO PERÍODO PERSONALIZADO
        |--------------------------------------------------------------------------
        | Evita consultas e gerações acidentais para períodos excessivamente
        | grandes. O limite atual é de cinco anos.
        */
        $limiteMaximo = $inicio
            ->copy()
            ->addYearsNoOverflow(5)
            ->endOfDay();

        if ($fim->gt($limiteMaximo)) {
            throw ValidationException::withMessages([
                'data_fim' => 'O período máximo permitido é de cinco anos.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | GARANTIR HISTÓRICO DAS MOVIMENTAÇÕES FIXAS
        |--------------------------------------------------------------------------
        | Gera as competências desde a primeira recorrência do usuário até
        | o final do período consultado. Isso permite que o saldo inicial
        | considere corretamente as competências anteriores ao relatório.
        */
        $geradorHistoricoFixo->gerar(
            (int) $user->id,
            $fim
        );

        $categoriaSelecionada = trim(
            (string) $request->input('categoria', '')
        );

        $formaPagamentoSelecionada = trim(
            (string) $request->input('forma_pagamento', '')
        );

        /*
        |--------------------------------------------------------------------------
        | SALDO ACUMULADO DO RELATÓRIO
        |--------------------------------------------------------------------------
        | O saldo inicial considera tudo que ocorreu antes da data inicial.
        | Os mesmos filtros analíticos do relatório são aplicados ao histórico
        | e ao período, mantendo a equação dos valores exibidos.
        */
        $aplicarFiltrosSaldo = function (
            Builder $consulta
        ) use (
            $tipo,
            $status,
            $categoriaSelecionada,
            $formaPagamentoSelecionada
        ): void {
            $this->aplicarFiltros(
                $consulta,
                $tipo,
                $status,
                $categoriaSelecionada,
                $formaPagamentoSelecionada
            );
        };

        $saldoAcumulado = $calculadorSaldo->calcular(
            (int) $user->id,
            $inicio,
            $fim,
            $aplicarFiltrosSaldo
        );

        $consultaBase = Movimentacao::query()
            ->where('user_id', $user->id)
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

        $this->aplicarFiltros(
            $consultaBase,
            $tipo,
            $status,
            $categoriaSelecionada,
            $formaPagamentoSelecionada
        );

        /*
        |--------------------------------------------------------------------------
        | DADOS COMPLETOS PARA RESUMOS E GRÁFICOS
        |--------------------------------------------------------------------------
        */
        $movimentacoesDoPeriodo = (clone $consultaBase)
            ->orderBy('data')
            ->orderBy('id')
            ->get([
                'id',
                'tipo',
                'descricao',
                'valor',
                'data',
                'data_pagamento',
                'categoria',
                'forma_pagamento',
                'status',
                'parcelado',
                'parcela_fixa',
                'parcela_atual',
                'total_parcelas',
                'despesa_fixa_id',
                'entrada_fixa_id',
                'fixo_mensal',
            ]);

        $resumo = $this->montarResumo(
            $movimentacoesDoPeriodo,
            $saldoAcumulado
        );

        $evolucao = $this->montarEvolucao(
            $movimentacoesDoPeriodo,
            $inicio,
            $fim
        );

        $porCategoria = $this->montarResumoPorCategoria(
            $movimentacoesDoPeriodo
        );

        /*
        |--------------------------------------------------------------------------
        | TABELA PAGINADA
        |--------------------------------------------------------------------------
        */
        $movimentacoes = (clone $consultaBase)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(
                fn (Movimentacao $movimentacao) =>
                    $this->serializarMovimentacao($movimentacao)
            );

        /*
        |--------------------------------------------------------------------------
        | OPÇÕES DOS FILTROS
        |--------------------------------------------------------------------------
        | As opções são buscadas em todas as movimentações do usuário,
        | independentemente do período selecionado.
        */
        $categorias = Movimentacao::query()
            ->where('user_id', $user->id)
            ->whereNotNull('categoria')
            ->where('categoria', '<>', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->values();

        $formasPagamento = Movimentacao::query()
            ->where('user_id', $user->id)
            ->whereNotNull('forma_pagamento')
            ->where('forma_pagamento', '<>', '')
            ->distinct()
            ->orderBy('forma_pagamento')
            ->pluck('forma_pagamento')
            ->values();

        return Inertia::render('relatorios/index', [
            'resumo' => $resumo,

            'graficos' => [
                'evolucao' => $evolucao,
                'porCategoria' => $porCategoria,
            ],

            'movimentacoes' => $movimentacoes,

            'filtros' => [
                'periodo' => $periodo,
                'referencia' => $referencia->format('Y-m-d'),
                'data_inicio' => $inicio->format('Y-m-d'),
                'data_fim' => $fim->format('Y-m-d'),
                'tipo' => $tipo,
                'status' => $status,
                'categoria' => $categoriaSelecionada,
                'forma_pagamento' => $formaPagamentoSelecionada,
            ],

            'periodoSelecionado' => [
                'inicio' => $inicio->format('Y-m-d'),
                'fim' => $fim->format('Y-m-d'),
                'titulo' => $this->montarTituloPeriodo(
                    $inicio,
                    $fim,
                    $periodo
                ),
            ],

            'opcoes' => [
                'categorias' => $categorias,
                'formasPagamento' => $formasPagamento,
            ],
        ]);
    }

    private function resolverReferencia(
        mixed $referencia
    ): Carbon {
        if (! is_string($referencia) || $referencia === '') {
            return Carbon::today();
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $referencia
            )->startOfDay();
        } catch (\Throwable) {
            return Carbon::today();
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverIntervalo(
        Request $request,
        string $periodo,
        Carbon $referencia
    ): array {
        if ($periodo === 'personalizado') {
            $request->validate([
                'data_inicio' => [
                    'required',
                    'date_format:Y-m-d',
                ],
                'data_fim' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:data_inicio',
                ],
            ]);

            return [
                Carbon::createFromFormat(
                    'Y-m-d',
                    (string) $request->input('data_inicio')
                )->startOfDay(),

                Carbon::createFromFormat(
                    'Y-m-d',
                    (string) $request->input('data_fim')
                )->endOfDay(),
            ];
        }

        return match ($periodo) {
            'semana' => [
                $referencia->copy()
                    ->startOfWeek(Carbon::MONDAY),

                $referencia->copy()
                    ->endOfWeek(Carbon::SUNDAY),
            ],

            'ano' => [
                $referencia->copy()->startOfYear(),
                $referencia->copy()->endOfYear(),
            ],

            default => [
                $referencia->copy()->startOfMonth(),
                $referencia->copy()->endOfMonth(),
            ],
        };
    }

    private function aplicarFiltros(
        Builder $consulta,
        string $tipo,
        string $status,
        string $categoria,
        string $formaPagamento
    ): void {
        if ($tipo === 'entrada') {
            $consulta->where('tipo', 'entrada');
        }

        if ($tipo === 'despesa') {
            $consulta->where('tipo', 'despesa');
        }

        if ($status === 'concluidos') {
            $consulta->where(function (Builder $query) {
                $query
                    ->whereIn('status', [
                        'pago',
                        'recebido',
                    ])
                    ->orWhereNotNull('data_pagamento');
            });
        }

        if ($status === 'pendentes') {
            $consulta
                ->where('status', 'pendente')
                ->whereNull('data_pagamento');
        }

        if ($categoria !== '') {
            $consulta->where('categoria', $categoria);
        }

        if ($formaPagamento !== '') {
            $consulta->where(
                'forma_pagamento',
                $formaPagamento
            );
        }
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     * @param array{
     *     saldo_inicial: float,
     *     entradas: float,
     *     despesas: float,
     *     total_disponivel: float,
     *     saldo_final: float
     * } $saldoAcumulado
     */
    private function montarResumo(
        Collection $movimentacoes,
        array $saldoAcumulado
    ): array {
        $totalEntradas = round(
            (float) $saldoAcumulado['entradas'],
            2
        );

        $totalDespesas = round(
            (float) $saldoAcumulado['despesas'],
            2
        );

        $pendentes = $movimentacoes->filter(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->status === 'pendente'
                && $movimentacao->data_pagamento === null
        );

        $concluidas = $movimentacoes->filter(
            fn (Movimentacao $movimentacao) =>
                in_array(
                    $movimentacao->status,
                    ['pago', 'recebido'],
                    true
                )
                || $movimentacao->data_pagamento !== null
        );

        return [
            'saldo_inicial' => round(
                (float) $saldoAcumulado['saldo_inicial'],
                2
            ),

            'entradas' => $totalEntradas,
            'despesas' => $totalDespesas,

            'total_disponivel' => round(
                (float) $saldoAcumulado['total_disponivel'],
                2
            ),

            'saldo' => round(
                (float) $saldoAcumulado['saldo_final'],
                2
            ),

            'quantidade' => $movimentacoes->count(),
            'concluidas' => $concluidas->count(),
            'pendentes' => $pendentes->count(),

            'valor_pendente' => round(
                (float) $pendentes->sum('valor'),
                2
            ),
        ];
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarEvolucao(
        Collection $movimentacoes,
        Carbon $inicio,
        Carbon $fim
    ): array {
        $quantidadeDias = $inicio->diffInDays($fim) + 1;

        if ($quantidadeDias <= 45) {
            return $this->montarEvolucaoDiaria(
                $movimentacoes,
                $inicio,
                $fim
            );
        }

        if ($quantidadeDias <= 1096) {
            return $this->montarEvolucaoMensal(
                $movimentacoes,
                $inicio,
                $fim
            );
        }

        return $this->montarEvolucaoAnual(
            $movimentacoes,
            $inicio,
            $fim
        );
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarEvolucaoDiaria(
        Collection $movimentacoes,
        Carbon $inicio,
        Carbon $fim
    ): array {
        $porData = $movimentacoes->groupBy(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->data->format('Y-m-d')
        );

        $pontos = [];

        for (
            $data = $inicio->copy();
            $data->lte($fim);
            $data->addDay()
        ) {
            $chave = $data->format('Y-m-d');
            $itens = $porData->get($chave, collect());

            $pontos[] = [
                'rotulo' => $data->format('d/m'),
                'data' => $chave,
                'entradas' => round(
                    (float) $itens
                        ->where('tipo', 'entrada')
                        ->sum('valor'),
                    2
                ),
                'despesas' => round(
                    (float) $itens
                        ->where('tipo', 'despesa')
                        ->sum('valor'),
                    2
                ),
            ];
        }

        return [
            'granularidade' => 'dia',
            'pontos' => $pontos,
        ];
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarEvolucaoMensal(
        Collection $movimentacoes,
        Carbon $inicio,
        Carbon $fim
    ): array {
        $porMes = $movimentacoes->groupBy(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->data->format('Y-m')
        );

        $pontos = [];
        $mes = $inicio->copy()->startOfMonth();
        $ultimoMes = $fim->copy()->startOfMonth();

        while ($mes->lte($ultimoMes)) {
            $chave = $mes->format('Y-m');
            $itens = $porMes->get($chave, collect());

            $pontos[] = [
                'rotulo' => $mes->format('m/Y'),
                'data' => $chave,
                'entradas' => round(
                    (float) $itens
                        ->where('tipo', 'entrada')
                        ->sum('valor'),
                    2
                ),
                'despesas' => round(
                    (float) $itens
                        ->where('tipo', 'despesa')
                        ->sum('valor'),
                    2
                ),
            ];

            $mes->addMonth();
        }

        return [
            'granularidade' => 'mês',
            'pontos' => $pontos,
        ];
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarEvolucaoAnual(
        Collection $movimentacoes,
        Carbon $inicio,
        Carbon $fim
    ): array {
        $porAno = $movimentacoes->groupBy(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->data->format('Y')
        );

        $pontos = [];

        for (
            $ano = $inicio->year;
            $ano <= $fim->year;
            $ano++
        ) {
            $chave = (string) $ano;
            $itens = $porAno->get($chave, collect());

            $pontos[] = [
                'rotulo' => $chave,
                'data' => $chave,
                'entradas' => round(
                    (float) $itens
                        ->where('tipo', 'entrada')
                        ->sum('valor'),
                    2
                ),
                'despesas' => round(
                    (float) $itens
                        ->where('tipo', 'despesa')
                        ->sum('valor'),
                    2
                ),
            ];
        }

        return [
            'granularidade' => 'ano',
            'pontos' => $pontos,
        ];
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarResumoPorCategoria(
        Collection $movimentacoes
    ): array {
        return $movimentacoes
            ->groupBy(
                fn (Movimentacao $movimentacao) =>
                    $movimentacao->categoria
                    ?: 'Sem categoria'
            )
            ->map(function (
                Collection $itens,
                string $categoria
            ) {
                return [
                    'categoria' => $categoria,
                    'entradas' => round(
                        (float) $itens
                            ->where('tipo', 'entrada')
                            ->sum('valor'),
                        2
                    ),
                    'despesas' => round(
                        (float) $itens
                            ->where('tipo', 'despesa')
                            ->sum('valor'),
                        2
                    ),
                    'quantidade' => $itens->count(),
                ];
            })
            ->sortByDesc(
                fn (array $item) =>
                    $item['entradas']
                    + $item['despesas']
            )
            ->values()
            ->all();
    }

    private function serializarMovimentacao(
        Movimentacao $movimentacao
    ): array {
        return [
            'id' => $movimentacao->id,
            'tipo' => $movimentacao->tipo,
            'descricao' => $movimentacao->descricao,
            'valor' => $movimentacao->valor,
            'data' => $movimentacao->data->format('Y-m-d'),
            'data_pagamento' =>
                $movimentacao->data_pagamento?->format('Y-m-d'),
            'categoria' => $movimentacao->categoria,
            'forma_pagamento' =>
                $movimentacao->forma_pagamento,
            'status' => $movimentacao->status,
            'parcelado' => (bool) $movimentacao->parcelado,
            'parcela_fixa' =>
                (bool) $movimentacao->parcela_fixa,
            'fixo_mensal' =>
                (bool) $movimentacao->fixo_mensal,
            'parcela_atual' =>
                $movimentacao->parcela_atual,
            'total_parcelas' =>
                $movimentacao->total_parcelas,
            'despesa_fixa_id' =>
                $movimentacao->despesa_fixa_id,
            'entrada_fixa_id' =>
                $movimentacao->entrada_fixa_id,
        ];
    }

    private function montarTituloPeriodo(
        Carbon $inicio,
        Carbon $fim,
        string $periodo
    ): string {
        return match ($periodo) {
            'semana' => sprintf(
                '%s a %s',
                $inicio->format('d/m/Y'),
                $fim->format('d/m/Y')
            ),

            'ano' => (string) $inicio->year,

            'personalizado' => sprintf(
                '%s a %s',
                $inicio->format('d/m/Y'),
                $fim->format('d/m/Y')
            ),

            default => sprintf(
                '%s de %s',
                $this->nomeMes($inicio->month),
                $inicio->year
            ),
        };
    }

    private function nomeMes(int $mes): string
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ][$mes];
    }
}
