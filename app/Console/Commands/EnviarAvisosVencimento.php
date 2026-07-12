<?php

namespace App\Console\Commands;

use App\Mail\AvisoVencimentoDespesaMail;
use App\Models\Movimentacao;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarAvisosVencimento extends Command
{
    protected $signature = 'avisos:vencimentos';

    protected $description = 'Envia avisos por e-mail para despesas pendentes que vencem hoje';

    public function handle(): int
    {
        $hoje = Carbon::today(config('app.timezone'))->toDateString();

        $despesasPorUsuario = Movimentacao::with('user')
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereDate('data', $hoje)
            ->whereNull('data_pagamento')
            ->where(function ($query) use ($hoje) {
                $query->whereNull('aviso_vencimento_enviado_em')
                    ->orWhereDate('aviso_vencimento_enviado_em', '!=', $hoje);
            })
            ->get()
            ->filter(function ($movimentacao) {
                return $movimentacao->user && $movimentacao->user->email;
            })
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
