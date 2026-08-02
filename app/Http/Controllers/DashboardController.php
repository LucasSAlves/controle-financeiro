<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Services\CalcularSaldoAcumulado;
use App\Services\GerarDespesasFixasMensais;
use App\Services\GerarEntradasFixasMensais;
use App\Services\GerarMovimentacoesFixasAteCompetencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        GerarDespesasFixasMensais $geradorDespesasFixas,
        GerarEntradasFixasMensais $geradorEntradasFixas,
        GerarMovimentacoesFixasAteCompetencia $geradorHistoricoFixo,
        CalcularSaldoAcumulado $calculadorSaldo
    ): Response
    {
        $user = $request->user();

        $mesSelecionado = $request->input('mes', now()->format('Y-m'));

        $inicioDoMes = Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->startOfMonth();

        $fimDoMes = Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->endOfMonth();

        $hoje = Carbon::today();

        $periodoGrafico = $request->input('periodo_grafico', 'mes');

        if (! in_array($periodoGrafico, ['semana', 'mes', 'ano'], true)) {
            $periodoGrafico = 'mes';
        }


        /*
        |--------------------------------------------------------------------------
        | GARANTIR HISTÓRICO DAS MOVIMENTAÇÕES FIXAS
        |--------------------------------------------------------------------------
        | Gera as competências fixas desde a primeira recorrência do usuário
        | até o mês selecionado. Os serviços individuais impedem duplicações
        | e respeitam meses excluídos e recorrências encerradas.
        */
        $geradorHistoricoFixo->gerar(
            (int) $user->id,
            $fimDoMes
        );

        /*
        |--------------------------------------------------------------------------
        | GERAR DESPESAS FIXAS NECESSÁRIAS
        |--------------------------------------------------------------------------
        | Gera:
        | - o mês selecionado no Dashboard;
        | - o mês atual;
        | - o mês que contém o final dos próximos sete dias.
        |
        | A lista remove competências repetidas.
        */
        $competenciasParaGerar = collect([
            $inicioDoMes->copy()->startOfMonth(),
            $hoje->copy()->startOfMonth(),
            $hoje->copy()->addDays(7)->startOfMonth(),
        ])->unique(
            fn ($competencia) => $competencia->format('Y-m')
        );

        foreach ($competenciasParaGerar as $competencia) {
            $geradorDespesasFixas->gerarParaMes(
                $competencia,
                (int) Auth::id()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GERAR ENTRADAS FIXAS DO MÊS SELECIONADO
        |--------------------------------------------------------------------------
        | Antes de calcular os totais, garante que as entradas fixas
        | estejam lançadas no mês consultado no Dashboard.
        */
        $geradorEntradasFixas->gerarParaMes(
            $inicioDoMes,
            (int) Auth::id()
        );

        $graficoGastos = $this->montarGraficoGastos(
            (int) $user->id,
            $periodoGrafico,
            $hoje
        );

        /*
        |--------------------------------------------------------------------------
        | SALDO ACUMULADO DO MÊS
        |--------------------------------------------------------------------------
        | Mantém a regra atual do Dashboard:
        | - cálculo pela data do lançamento;
        | - todas as entradas;
        | - todas as despesas;
        | - sem filtro de status.
        */
        $saldoAcumulado = $calculadorSaldo->calcular(
            (int) $user->id,
            $inicioDoMes,
            $fimDoMes
        );

        $saldoAnterior = $saldoAcumulado['saldo_inicial'];
        $totalEntradas = $saldoAcumulado['entradas'];
        $totalDespesas = $saldoAcumulado['despesas'];
        $totalDisponivel = $saldoAcumulado['total_disponivel'];
        $saldo = $saldoAcumulado['saldo_final'];

        $ultimasMovimentacoes = Movimentacao::where('user_id', Auth::id())
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo,
                    'descricao' => $movimentacao->descricao,
                    'valor' => $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' => $movimentacao->forma_pagamento,
                    'status' => $movimentacao->status,
                    'parcelado' => $movimentacao->parcelado,
                    'parcela_fixa' => $movimentacao->parcela_fixa,
                    'despesa_fixa_id' => $movimentacao->despesa_fixa_id,

                    'fixo_mensal' => $movimentacao->fixo_mensal,
                    'entrada_fixa_id' => $movimentacao->entrada_fixa_id,

                    'parcela_atual' => $movimentacao->parcela_atual,
                    'total_parcelas' => $movimentacao->total_parcelas,
                ];
            });

        $parcelasVencidas = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereDate('data', '<', $hoje)
            ->orderBy('data')
            ->limit(10)
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'descricao' => $movimentacao->descricao,
                    'valor' => $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' => $movimentacao->forma_pagamento,
                    'parcelado' => $movimentacao->parcelado,
                    'parcela_fixa' => $movimentacao->parcela_fixa,
                    'despesa_fixa_id' => $movimentacao->despesa_fixa_id,
                    'parcela_atual' => $movimentacao->parcela_atual,
                    'total_parcelas' => $movimentacao->total_parcelas,
                ];
            });

        $parcelasVencemHoje = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereDate('data', $hoje)
            ->orderBy('data')
            ->limit(10)
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'descricao' => $movimentacao->descricao,
                    'valor' => $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' => $movimentacao->forma_pagamento,
                    'parcelado' => $movimentacao->parcelado,
                    'parcela_fixa' => $movimentacao->parcela_fixa,
                    'despesa_fixa_id' => $movimentacao->despesa_fixa_id,
                    'parcela_atual' => $movimentacao->parcela_atual,
                    'total_parcelas' => $movimentacao->total_parcelas,
                ];
            });

        $parcelasProximosDias = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereDate('data', '>', $hoje)
            ->whereDate('data', '<=', $hoje->copy()->addDays(7))
            ->orderBy('data')
            ->limit(10)
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'descricao' => $movimentacao->descricao,
                    'valor' => $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' => $movimentacao->forma_pagamento,
                    'parcelado' => $movimentacao->parcelado,
                    'parcela_fixa' => $movimentacao->parcela_fixa,
                    'despesa_fixa_id' => $movimentacao->despesa_fixa_id,
                    'parcela_atual' => $movimentacao->parcela_atual,
                    'total_parcelas' => $movimentacao->total_parcelas,
                ];
            });

        return Inertia::render('dashboard', [
            'preferenciasNotificacao' => [
                'definidas' => $user->preferencias_notificacao_definidas_em !== null,
                'receber_aviso_email' => (bool) $user->receber_aviso_email,
                'receber_aviso_whatsapp' => (bool) $user->receber_aviso_whatsapp,
                'telefone_whatsapp' => $user->telefone_whatsapp,
            ],
            'resumo' => [
                'saldo_anterior' => $saldoAnterior,
                'entradas' => $totalEntradas,
                'despesas' => $totalDespesas,
                'total_disponivel' => $totalDisponivel,
                'saldo' => $saldo,
            ],
            'graficoGastos' => $graficoGastos,
            'ultimasMovimentacoes' => $ultimasMovimentacoes,
            'avisosVencimento' => [
                'vencidas' => $parcelasVencidas,
                'vencemHoje' => $parcelasVencemHoje,
                'proximosDias' => $parcelasProximosDias,
            ],
            'filtros' => [
                'mes' => $mesSelecionado,
                'periodo_grafico' => $periodoGrafico,
            ],
        ]);
    }

    private function montarGraficoGastos(
        int $userId,
        string $periodo,
        Carbon $referencia
    ): array {
        [$inicio, $fim] = match ($periodo) {
            'semana' => [
                $referencia->copy()->startOfWeek(Carbon::MONDAY),
                $referencia->copy()->endOfWeek(Carbon::SUNDAY),
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

        $inicioAnterior = match ($periodo) {
            'semana' => $inicio->copy()->subWeek(),
            'ano' => $inicio->copy()->subYear()->startOfYear(),
            default => $inicio->copy()->subMonthNoOverflow()->startOfMonth(),
        };

        $fimAnterior = $inicio->copy()->subDay();

        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÕES DO PERÍODO
        |--------------------------------------------------------------------------
        | O gráfico recebe entradas e despesas. Isso permite mostrar:
        | - somente despesas;
        | - somente entradas;
        | - visão geral com as duas linhas.
        */
        $movimentacoesDoPeriodo = Movimentacao::query()
            ->where('user_id', $userId)
            ->whereIn('tipo', ['entrada', 'despesa'])
            ->whereBetween('data', [
                $inicio->toDateString(),
                $fim->toDateString(),
            ])
            ->get([
                'tipo',
                'valor',
                'data',
            ]);

        $movimentacoesDoPeriodoAnterior = Movimentacao::query()
            ->where('user_id', $userId)
            ->whereIn('tipo', ['entrada', 'despesa'])
            ->whereBetween('data', [
                $inicioAnterior->toDateString(),
                $fimAnterior->toDateString(),
            ])
            ->get([
                'tipo',
                'valor',
            ]);

        $totalEntradas = round(
            (float) $movimentacoesDoPeriodo
                ->where('tipo', 'entrada')
                ->sum('valor'),
            2
        );

        $totalDespesas = round(
            (float) $movimentacoesDoPeriodo
                ->where('tipo', 'despesa')
                ->sum('valor'),
            2
        );

        $totalEntradasAnterior = round(
            (float) $movimentacoesDoPeriodoAnterior
                ->where('tipo', 'entrada')
                ->sum('valor'),
            2
        );

        $totalDespesasAnterior = round(
            (float) $movimentacoesDoPeriodoAnterior
                ->where('tipo', 'despesa')
                ->sum('valor'),
            2
        );

        $saldoAtual = round(
            $totalEntradas - $totalDespesas,
            2
        );

        $saldoAnterior = round(
            $totalEntradasAnterior - $totalDespesasAnterior,
            2
        );

        $pontos = $periodo === 'ano'
            ? $this->montarPontosAnuais(
                $movimentacoesDoPeriodo,
                $referencia->year
            )
            : $this->montarPontosDiarios(
                $movimentacoesDoPeriodo,
                $inicio,
                $fim,
                $periodo === 'semana'
            );

        $divisorMedia = match ($periodo) {
            'semana' => max(1, $referencia->dayOfWeekIso),
            'ano' => max(1, $referencia->month),
            default => max(1, $referencia->day),
        };

        $tituloPeriodo = match ($periodo) {
            'semana' => sprintf(
                '%s a %s',
                $inicio->format('d/m'),
                $fim->format('d/m/Y')
            ),
            'ano' => (string) $referencia->year,
            default => sprintf(
                '%s de %s',
                $this->nomeCompletoMes($referencia->month),
                $referencia->year
            ),
        };

        return [
            'periodo' => $periodo,
            'titulo' => $tituloPeriodo,
            'unidade_media' => $periodo === 'ano' ? 'mês' : 'dia',

            /*
            | Campos antigos mantidos temporariamente.
            | O gráfico atual continuará exibindo despesas normalmente.
            */
            'total' => $totalDespesas,
            'media' => round($totalDespesas / $divisorMedia, 2),
            'total_anterior' => $totalDespesasAnterior,
            'comparacao_percentual' => $this->calcularComparacaoPercentual(
                $totalDespesas,
                $totalDespesasAnterior
            ),

            /*
            | Nova estrutura completa.
            */
            'totais' => [
                'entradas' => $totalEntradas,
                'despesas' => $totalDespesas,
                'saldo' => $saldoAtual,
            ],
            'medias' => [
                'entradas' => round($totalEntradas / $divisorMedia, 2),
                'despesas' => round($totalDespesas / $divisorMedia, 2),
            ],
            'anterior' => [
                'entradas' => $totalEntradasAnterior,
                'despesas' => $totalDespesasAnterior,
                'saldo' => $saldoAnterior,
            ],
            'comparacoes' => [
                'entradas' => $this->calcularComparacaoPercentual(
                    $totalEntradas,
                    $totalEntradasAnterior
                ),
                'despesas' => $this->calcularComparacaoPercentual(
                    $totalDespesas,
                    $totalDespesasAnterior
                ),
            ],
            'pontos' => $pontos,
        ];
    }
    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarPontosDiarios(
        Collection $movimentacoes,
        Carbon $inicio,
        Carbon $fim,
        bool $usarDiaDaSemana
    ): array {
        $movimentacoesPorData = $movimentacoes->groupBy(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->data->format('Y-m-d')
        );

        $nomesDias = [
            1 => 'Seg',
            2 => 'Ter',
            3 => 'Qua',
            4 => 'Qui',
            5 => 'Sex',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        $pontos = [];

        for (
            $data = $inicio->copy();
            $data->lte($fim);
            $data->addDay()
        ) {
            $chave = $data->format('Y-m-d');

            $movimentacoesDoDia = $movimentacoesPorData->get(
                $chave,
                collect()
            );

            $entradas = round(
                (float) $movimentacoesDoDia
                    ->where('tipo', 'entrada')
                    ->sum('valor'),
                2
            );

            $despesas = round(
                (float) $movimentacoesDoDia
                    ->where('tipo', 'despesa')
                    ->sum('valor'),
                2
            );

            $pontos[] = [
                'rotulo' => $usarDiaDaSemana
                    ? $nomesDias[$data->dayOfWeekIso]
                    : $data->format('d'),
                'data' => $chave,

                // Compatibilidade com a linha atual do gráfico.
                'valor' => $despesas,

                // Novas séries.
                'entradas' => $entradas,
                'despesas' => $despesas,
            ];
        }

        return $pontos;
    }

    /**
     * @param Collection<int, Movimentacao> $movimentacoes
     */
    private function montarPontosAnuais(
        Collection $movimentacoes,
        int $ano
    ): array {
        $movimentacoesPorMes = $movimentacoes->groupBy(
            fn (Movimentacao $movimentacao) =>
                $movimentacao->data->format('Y-m')
        );

        $nomesMeses = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        $pontos = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $chave = sprintf('%04d-%02d', $ano, $mes);

            $movimentacoesDoMes = $movimentacoesPorMes->get(
                $chave,
                collect()
            );

            $entradas = round(
                (float) $movimentacoesDoMes
                    ->where('tipo', 'entrada')
                    ->sum('valor'),
                2
            );

            $despesas = round(
                (float) $movimentacoesDoMes
                    ->where('tipo', 'despesa')
                    ->sum('valor'),
                2
            );

            $pontos[] = [
                'rotulo' => $nomesMeses[$mes],
                'data' => $chave,

                // Compatibilidade temporária com o gráfico atual.
                'valor' => $despesas,

                // Valores usados pela nova visualização.
                'entradas' => $entradas,
                'despesas' => $despesas,
            ];
        }

        return $pontos;
    }

    private function calcularComparacaoPercentual(
        float $totalAtual,
        float $totalAnterior
    ): ?float {
        if ($totalAnterior <= 0) {
            return null;
        }

        return round(
            (($totalAtual - $totalAnterior) / $totalAnterior) * 100,
            1
        );
    }

    private function nomeCompletoMes(int $mes): string
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
