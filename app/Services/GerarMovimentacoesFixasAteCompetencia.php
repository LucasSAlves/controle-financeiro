<?php

namespace App\Services;

use App\Models\DespesaFixa;
use App\Models\EntradaFixa;
use Carbon\Carbon;

class GerarMovimentacoesFixasAteCompetencia
{
    public function __construct(
        private GerarDespesasFixasMensais $geradorDespesasFixas,
        private GerarEntradasFixasMensais $geradorEntradasFixas
    ) {
    }

    /**
     * Garante que todas as competências fixas do usuário tenham sido
     * geradas desde a primeira recorrência até o mês informado.
     *
     * Os geradores individuais impedem duplicações e respeitam:
     * - meses excluídos;
     * - recorrências encerradas;
     * - alterações nas regras;
     * - movimentações já existentes.
     *
     * @return array{
     *     competencias_processadas: int,
     *     despesas_criadas: int,
     *     entradas_criadas: int
     * }
     */
    public function gerar(
        int $userId,
        Carbon|string $ate
    ): array {
        $ultimaCompetencia = $ate instanceof Carbon
            ? $ate->copy()->startOfMonth()
            : Carbon::parse($ate)->startOfMonth();

        /*
        |--------------------------------------------------------------------------
        | PRIMEIRA RECORRÊNCIA DO USUÁRIO
        |--------------------------------------------------------------------------
        | O histórico começa na data mais antiga entre despesas e entradas
        | fixas. Movimentações comuns já estão registradas no banco e não
        | precisam ser geradas.
        */
        $primeiraDespesaFixa = DespesaFixa::query()
            ->where('user_id', $userId)
            ->min('data_inicio');

        $primeiraEntradaFixa = EntradaFixa::query()
            ->where('user_id', $userId)
            ->min('data_inicio');

        $datasIniciais = collect([
            $primeiraDespesaFixa,
            $primeiraEntradaFixa,
        ])
            ->filter(
                fn ($data) =>
                    $data !== null
                    && $data !== ''
            )
            ->map(
                fn ($data) =>
                    Carbon::parse($data)->startOfMonth()
            )
            ->sortBy(
                fn (Carbon $data) =>
                    $data->getTimestamp()
            )
            ->values();

        /*
         * O usuário pode não possuir nenhuma recorrência fixa.
         */
        if ($datasIniciais->isEmpty()) {
            return [
                'competencias_processadas' => 0,
                'despesas_criadas' => 0,
                'entradas_criadas' => 0,
            ];
        }

        /** @var Carbon $primeiraCompetencia */
        $primeiraCompetencia = $datasIniciais
            ->first()
            ->copy();

        /*
         * Não há competência para gerar quando a primeira recorrência
         * começa depois do período consultado.
         */
        if ($primeiraCompetencia->gt($ultimaCompetencia)) {
            return [
                'competencias_processadas' => 0,
                'despesas_criadas' => 0,
                'entradas_criadas' => 0,
            ];
        }

        $competenciasProcessadas = 0;
        $despesasCriadas = 0;
        $entradasCriadas = 0;

        for (
            $competencia = $primeiraCompetencia->copy();
            $competencia->lte($ultimaCompetencia);
            $competencia->addMonth()
        ) {
            $despesasCriadas +=
                $this->geradorDespesasFixas->gerarParaMes(
                    $competencia->copy(),
                    $userId
                );

            $entradasCriadas +=
                $this->geradorEntradasFixas->gerarParaMes(
                    $competencia->copy(),
                    $userId
                );

            $competenciasProcessadas++;
        }

        return [
            'competencias_processadas' =>
                $competenciasProcessadas,

            'despesas_criadas' =>
                $despesasCriadas,

            'entradas_criadas' =>
                $entradasCriadas,
        ];
    }
}
