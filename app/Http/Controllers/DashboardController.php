<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use App\Services\GerarDespesasFixasMensais;
use App\Services\GerarEntradasFixasMensais;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        GerarDespesasFixasMensais $geradorDespesasFixas,
        GerarEntradasFixasMensais $geradorEntradasFixas
    ): Response
    {
        $user = $request->user();

        $mesSelecionado = $request->input('mes', now()->format('Y-m'));

        $inicioDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->startOfMonth();

        $fimDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->endOfMonth();

        $hoje = \Carbon\Carbon::today();

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

        $totalEntradas = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'entrada')
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->sum('valor');

        $totalDespesas = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->sum('valor');

        $saldo = $totalEntradas - $totalDespesas;

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
                'entradas' => $totalEntradas,
                'despesas' => $totalDespesas,
                'saldo' => $saldo,
            ],
            'ultimasMovimentacoes' => $ultimasMovimentacoes,
            'avisosVencimento' => [
                'vencidas' => $parcelasVencidas,
                'vencemHoje' => $parcelasVencemHoje,
                'proximosDias' => $parcelasProximosDias,
            ],
            'filtros' => [
                'mes' => $mesSelecionado,
            ],
        ]);
    }
}
