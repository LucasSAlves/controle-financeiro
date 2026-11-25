<?php

namespace Tests\Feature;

use App\Models\EntradaFixa;
use App\Models\Movimentacao;
use App\Models\User;
use App\Services\CalcularPrevisaoFinanceira;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrevisaoFinanceiraTest extends TestCase
{
    use RefreshDatabase;

    public function test_previsao_carrega_saldo_e_separa_atrasos_ate_o_pagamento(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->startOfDay());
        $user = User::factory()->create();
        $criar = fn (array $dados) => Movimentacao::create($dados + [
            'user_id' => $user->id,
            'descricao' => 'Lançamento de teste',
            'categoria' => 'Teste',
        ]);

        $criar(['tipo' => 'entrada', 'valor' => 500, 'data' => '2026-09-01', 'status' => 'recebido']);
        $antiga = $criar(['tipo' => 'despesa', 'valor' => 200, 'data' => '2026-09-24', 'status' => 'pendente']);
        $criar(['tipo' => 'despesa', 'valor' => 100, 'data' => '2026-09-25', 'status' => 'pendente']);
        $criar(['tipo' => 'despesa', 'valor' => 700, 'data' => '2026-12-10', 'status' => 'pendente']);

        $entrada = EntradaFixa::create([
            'user_id' => $user->id,
            'descricao' => 'Receita mensal',
            'valor' => 50,
            'data_inicio' => '2026-11-01',
            'dia_recebimento' => 30,
            'categoria' => 'Teste',
            'ativa' => true,
        ]);
        $criar(['entrada_fixa_id' => $entrada->id, 'tipo' => 'entrada', 'valor' => 50, 'data' => '2026-11-30', 'status' => 'recebido']);

        $previsao = app(CalcularPrevisaoFinanceira::class);
        $antes = $previsao->calcular($user->id);

        $this->assertSame([55000, -10000, -5000], array_column($antes['meses'], 'saldo_final'));
        $this->assertSame([20000], array_column($antes['pendencias']['antigas'], 'valor'));
        $this->assertSame([10000], array_column($antes['pendencias']['recentes'], 'valor'));
        $this->assertDatabaseCount('movimentacoes', 5);

        $antiga->update(['status' => 'pago', 'data_pagamento' => '2026-11-25']);
        $depois = $previsao->calcular($user->id);

        $this->assertSame([35000, -30000, -25000], array_column($depois['meses'], 'saldo_final'));
        $this->assertSame([], $depois['pendencias']['antigas']);
        $this->assertDatabaseCount('movimentacoes', 5);
    }
}
