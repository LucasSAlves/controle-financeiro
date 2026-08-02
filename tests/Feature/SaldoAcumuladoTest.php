<?php

use App\Models\Movimentacao;
use App\Models\User;
use App\Services\CalcularSaldoAcumulado;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function criarMovimentacaoParaSaldo(
    User $user,
    string $tipo,
    float $valor,
    string $data,
    ?string $status = null,
    ?string $dataPagamento = null
): Movimentacao {
    return Movimentacao::query()->create([
        'user_id' => $user->id,
        'tipo' => $tipo,
        'descricao' => sprintf(
            '%s de teste',
            $tipo === 'entrada' ? 'Entrada' : 'Despesa'
        ),
        'valor' => $valor,
        'data' => $data,
        'data_pagamento' => $dataPagamento,
        'categoria' => 'Teste',
        'forma_pagamento' =>
            $tipo === 'despesa' ? 'Teste' : null,
        'status' => $status ?? (
            $tipo === 'entrada'
                ? 'recebido'
                : 'pendente'
        ),
        'observacao' => null,
    ]);
}

/**
 * @return array{
 *     saldo_inicial: float,
 *     entradas: float,
 *     despesas: float,
 *     total_disponivel: float,
 *     saldo_final: float
 * }
 */
function calcularSaldoDoPeriodo(
    User $user,
    string $inicio,
    string $fim
): array {
    return app(CalcularSaldoAcumulado::class)->calcular(
        (int) $user->id,
        Carbon::parse($inicio)->startOfDay(),
        Carbon::parse($fim)->endOfDay()
    );
}

test('carrega saldo positivo para o mês seguinte', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-07-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        500,
        '2026-07-10'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-08-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        300,
        '2026-08-10'
    );

    $julho = calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    expect($julho['saldo_inicial'])->toBe(0.0)
        ->and($julho['entradas'])->toBe(1000.0)
        ->and($julho['despesas'])->toBe(500.0)
        ->and($julho['total_disponivel'])->toBe(1000.0)
        ->and($julho['saldo_final'])->toBe(500.0);

    $agosto = calcularSaldoDoPeriodo(
        $user,
        '2026-08-01',
        '2026-08-31'
    );

    expect($agosto['saldo_inicial'])->toBe(500.0)
        ->and($agosto['entradas'])->toBe(1000.0)
        ->and($agosto['despesas'])->toBe(300.0)
        ->and($agosto['total_disponivel'])->toBe(1500.0)
        ->and($agosto['saldo_final'])->toBe(1200.0);
});

test('carrega saldo negativo para o mês seguinte', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-07-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        1200,
        '2026-07-10'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-08-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        300,
        '2026-08-10'
    );

    $agosto = calcularSaldoDoPeriodo(
        $user,
        '2026-08-01',
        '2026-08-31'
    );

    expect($agosto['saldo_inicial'])->toBe(-200.0)
        ->and($agosto['entradas'])->toBe(1000.0)
        ->and($agosto['despesas'])->toBe(300.0)
        ->and($agosto['total_disponivel'])->toBe(800.0)
        ->and($agosto['saldo_final'])->toBe(500.0);
});

test('acumula corretamente o resultado de vários meses', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        500,
        '2026-01-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        400,
        '2026-01-10'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        600,
        '2026-02-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        400,
        '2026-02-10'
    );

    $marco = calcularSaldoDoPeriodo(
        $user,
        '2026-03-01',
        '2026-03-31'
    );

    expect($marco['saldo_inicial'])->toBe(300.0)
        ->and($marco['entradas'])->toBe(0.0)
        ->and($marco['despesas'])->toBe(0.0)
        ->and($marco['saldo_final'])->toBe(300.0);
});

test('mês sem entradas desconta somente suas despesas', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        800,
        '2026-06-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        300,
        '2026-06-10'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        200,
        '2026-07-10'
    );

    $julho = calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    expect($julho['saldo_inicial'])->toBe(500.0)
        ->and($julho['entradas'])->toBe(0.0)
        ->and($julho['despesas'])->toBe(200.0)
        ->and($julho['total_disponivel'])->toBe(500.0)
        ->and($julho['saldo_final'])->toBe(300.0);
});

test('mês vazio mantém o saldo acumulado anterior', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        700,
        '2026-06-05'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        200,
        '2026-06-10'
    );

    $julho = calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    expect($julho['saldo_inicial'])->toBe(500.0)
        ->and($julho['entradas'])->toBe(0.0)
        ->and($julho['despesas'])->toBe(0.0)
        ->and($julho['total_disponivel'])->toBe(500.0)
        ->and($julho['saldo_final'])->toBe(500.0);
});

test('não mistura movimentações de usuários diferentes', function () {
    $primeiroUsuario = User::factory()->create();
    $segundoUsuario = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $primeiroUsuario,
        'entrada',
        1000,
        '2026-07-05'
    );

    criarMovimentacaoParaSaldo(
        $primeiroUsuario,
        'despesa',
        400,
        '2026-07-10'
    );

    criarMovimentacaoParaSaldo(
        $segundoUsuario,
        'entrada',
        9000,
        '2026-07-05'
    );

    criarMovimentacaoParaSaldo(
        $segundoUsuario,
        'despesa',
        1000,
        '2026-07-10'
    );

    $saldoPrimeiroUsuario = calcularSaldoDoPeriodo(
        $primeiroUsuario,
        '2026-08-01',
        '2026-08-31'
    );

    $saldoSegundoUsuario = calcularSaldoDoPeriodo(
        $segundoUsuario,
        '2026-08-01',
        '2026-08-31'
    );

    expect($saldoPrimeiroUsuario['saldo_inicial'])
        ->toBe(600.0)
        ->and($saldoSegundoUsuario['saldo_inicial'])
        ->toBe(8000.0);
});

test('período personalizado considera tudo antes da data inicial', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-07-10'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        200,
        '2026-07-14'
    );

    /*
     * Esta movimentação ocorre na própria data inicial
     * e deve entrar no período, não no saldo inicial.
     */
    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        300,
        '2026-07-15'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        100,
        '2026-07-20'
    );

    $resultado = calcularSaldoDoPeriodo(
        $user,
        '2026-07-15',
        '2026-07-31'
    );

    expect($resultado['saldo_inicial'])->toBe(800.0)
        ->and($resultado['entradas'])->toBe(300.0)
        ->and($resultado['despesas'])->toBe(100.0)
        ->and($resultado['total_disponivel'])->toBe(1100.0)
        ->and($resultado['saldo_final'])->toBe(1000.0);
});

test('mantém a regra atual de data e inclui movimentações pendentes', function () {
    $user = User::factory()->create();

    criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-07-05',
        'recebido'
    );

    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        400,
        '2026-07-10',
        'pendente'
    );

    /*
     * A data de pagamento está em agosto, mas a movimentação
     * pertence a julho pelo campo data.
     */
    criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        100,
        '2026-07-20',
        'pago',
        '2026-08-02'
    );

    $julho = calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    expect($julho['entradas'])->toBe(1000.0)
        ->and($julho['despesas'])->toBe(500.0)
        ->and($julho['saldo_final'])->toBe(500.0);
});

test('o cálculo não cria nem altera movimentações', function () {
    $user = User::factory()->create();

    $entrada = criarMovimentacaoParaSaldo(
        $user,
        'entrada',
        1000,
        '2026-07-05'
    );

    $despesa = criarMovimentacaoParaSaldo(
        $user,
        'despesa',
        500,
        '2026-07-10'
    );

    $quantidadeAntes = Movimentacao::query()->count();

    calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    calcularSaldoDoPeriodo(
        $user,
        '2026-07-01',
        '2026-07-31'
    );

    expect(Movimentacao::query()->count())
        ->toBe($quantidadeAntes);

    $entrada->refresh();
    $despesa->refresh();

    expect((float) $entrada->valor)
        ->toBe(1000.0)
        ->and($entrada->data->format('Y-m-d'))
        ->toBe('2026-07-05')
        ->and((float) $despesa->valor)
        ->toBe(500.0)
        ->and($despesa->data->format('Y-m-d'))
        ->toBe('2026-07-10');
});
