<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $mesSelecionado = $request->input('mes', now()->format('Y-m'));

        $inicioDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->startOfMonth();

        $fimDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->endOfMonth();

        $hoje = \Carbon\Carbon::today();

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
