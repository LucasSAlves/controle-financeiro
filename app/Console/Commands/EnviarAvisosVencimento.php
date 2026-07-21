<?php

namespace App\Console\Commands;

use App\Mail\AvisoVencimentoDespesaMail;
use App\Models\Movimentacao;
use App\Services\GerarDespesasFixasMensais;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarAvisosVencimento extends Command
{
    protected $signature = 'avisos:vencimentos';

    protected $description = 'Envia avisos por e-mail para despesas pendentes que vencem hoje';

    public function handle(
        GerarDespesasFixasMensais $geradorDespesasFixas
    ): int
    {
        $dataHoje = Carbon::today(config('app.timezone'));
        $hoje = $dataHoje->toDateString();

        /*
        |--------------------------------------------------------------------------
        | GERAR DESPESAS FIXAS DO MÊS ATUAL
        |--------------------------------------------------------------------------
        | O comando é executado pelo agendador mesmo quando nenhum usuário
        | acessa o sistema. Passando userId como null, gera para todos os usuários.
        */
        $quantidadeGerada = $geradorDespesasFixas->gerarParaMes(
            $dataHoje->copy()->startOfMonth()
        );

        if ($quantidadeGerada > 0) {
            $this->info(
                "{$quantidadeGerada} lançamento(s) de despesa fixa gerado(s)."
            );
        }

        $despesasPorUsuario = Movimentacao::with('user')
            ->whereHas('user', function ($query) {
                $query->where('receber_aviso_email', true)
                    ->whereNotNull('email');
            })
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereDate('data', $hoje)
            ->whereNull('data_pagamento')
            ->where(function ($query) use ($hoje) {
                $query->whereNull('aviso_vencimento_enviado_em')
                    ->orWhereDate('aviso_vencimento_enviado_em', '!=', $hoje);
            })
            ->get()
            ->groupBy('user_id');

        if ($despesasPorUsuario->isEmpty()) {
            $this->info('Nenhuma despesa pendente vencendo hoje ou todos os avisos já foram enviados.');
            return self::SUCCESS;
        }

        foreach ($despesasPorUsuario as $despesas) {
            $user = $despesas->first()->user;

            Mail::to($user->email)->send(
                new AvisoVencimentoDespesaMail($user, $despesas)
            );

            Movimentacao::whereIn('id', $despesas->pluck('id'))
                ->update([
                    'aviso_vencimento_enviado_em' => $hoje,
                ]);

            $this->info("Aviso enviado para {$user->email}");
        }

        return self::SUCCESS;
    }
}
