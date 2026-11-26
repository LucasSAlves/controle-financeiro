<?php

namespace App\Services;

use App\Models\Movimentacao;
use Carbon\Carbon;

class CalcularPrevisaoFinanceira
{
    public function __construct(
        private GerarEntradasFixasMensais $entradasFixas,
        private GerarDespesasFixasMensais $despesasFixas,
    ) {}

    public function calcular(int $userId): array
    {
        $hoje = Carbon::today(config('app.timezone'));
        $inicio = $hoje->copy()->startOfMonth();
        $fim = $inicio->copy()->addMonths(2)->endOfMonth()->toDateString();
        $movimentacoes = Movimentacao::where('user_id', $userId);
        $entradas = (clone $movimentacoes)->where('tipo', 'entrada');
        $despesas = (clone $movimentacoes)->where('tipo', 'despesa');
        $pagas = (clone $despesas)->where('status', 'pago');
        $dataEfetiva = 'COALESCE(data_pagamento, data)';

        $saldo = self::centavos((clone $entradas)->where('data', '<', $inicio->toDateString())->sum('valor'))
            - self::centavos((clone $pagas)->whereRaw("$dataEfetiva < ?", [$inicio->toDateString()])->sum('valor'));

        $abertas = (clone $despesas)->where('status', 'pendente');
        $vencidas = (clone $abertas)->where('data', '<', $hoje->toDateString())->get()->all();
        $lancamentos = $entradas->whereBetween('data', [$inicio->toDateString(), $fim])->get()
            ->concat($pagas->whereRaw("$dataEfetiva BETWEEN ? AND ?", [$inicio->toDateString(), $fim])->get())
            ->concat($abertas->whereBetween('data', [$hoje->toDateString(), $fim])->get())
            ->groupBy(fn (Movimentacao $item) => $this->dataEfetiva($item)->format('Y-m'));

        $meses = [];
        for ($offset = 0; $offset < 3; $offset++) {
            $mes = $inicio->copy()->addMonths($offset);
            $competencia = $mes->format('Y-m');
            $itens = $lancamentos->get($competencia, collect())
                ->concat($this->entradasFixas->preverParaMes($mes, $userId))
                ->concat($this->despesasFixas->preverParaMes($mes, $userId))
                ->sortBy(fn (Movimentacao $item) => $this->dataEfetiva($item)->toDateString());
            $totais = ['entradas' => 0, 'despesas' => 0];
            $detalhes = [];

            foreach ($itens as $item) {
                if ($item->tipo === 'despesa' && $item->status === 'pendente' && $item->data->lt($hoje)) {
                    $vencidas[] = $item;
                    continue;
                }

                $totais[$item->tipo === 'entrada' ? 'entradas' : 'despesas'] += self::centavos($item->valor);
                $detalhes[] = $this->detalhe($item, $hoje);
            }

            $meses[] = [
                'competencia' => $competencia,
                'saldo_inicial' => $saldo,
                ...$totais,
                'saldo_final' => $saldo + $totais['entradas'] - $totais['despesas'],
                'lancamentos' => $detalhes,
            ];
            $saldo = $meses[$offset]['saldo_final'];
        }

        $pendencias = ['recentes' => [], 'antigas' => []];
        foreach (collect($vencidas)->sortBy('data') as $item) {
            $limite = $item->data->copy()->addMonthsNoOverflow(2);
            $grupo = $hoje->gt($limite) ? 'antigas' : 'recentes';
            $pendencias[$grupo][] = $this->detalhe($item, $hoje);
        }

        return ['meses' => $meses, 'pendencias' => $pendencias];
    }

    private static function centavos(float|int|string $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    private function dataEfetiva(Movimentacao $item): Carbon
    {
        return $item->status === 'pago' ? ($item->data_pagamento ?? $item->data) : $item->data;
    }

    private function detalhe(Movimentacao $item, Carbon $hoje): array
    {
        return [
            'id' => $item->id,
            'descricao' => $item->descricao,
            'valor' => self::centavos($item->valor),
            'tipo' => $item->tipo,
            'data_original' => $item->data->toDateString(),
            'data' => $this->dataEfetiva($item)->toDateString(),
            'realizado' => $item->exists
                && ($item->status === 'pago' || ($item->tipo === 'entrada' && $item->data->lte($hoje))),
        ];
    }
}
