<?php

namespace App\Http\Controllers;

use App\Models\FormaPagamento;
use App\Models\Categoria;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MovimentacaoController extends Controller
{
    public function index(Request $request): Response {
        $mesSelecionado = $request->input('mes', now()->format('Y-m'));
        $tipoSelecionado = $request->input('tipo', 'todos');
        $statusSelecionado = $request->input('status', 'todos');

        $inicioDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->startOfMonth();

        $fimDoMes = \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)
            ->endOfMonth();

        $queryMovimentacoes = Movimentacao::where('user_id', Auth::id())
        ->whereBetween('data', [$inicioDoMes, $fimDoMes]);

        if ($tipoSelecionado !== 'todos') {
            $queryMovimentacoes->where('tipo', $tipoSelecionado);
        }

        if ($statusSelecionado !== 'todos') {
            $queryMovimentacoes->where('status', $statusSelecionado);
        }

        $movimentacoes = $queryMovimentacoes
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo,
                    'descricao' => $movimentacao->descricao,
                    'valor' => $movimentacao->valor,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'data_pagamento' => $movimentacao->data_pagamento
                        ? $movimentacao->data_pagamento->format('Y-m-d')
                        : null,
                    'categoria' => $movimentacao->categoria,
                    'forma_pagamento' => $movimentacao->forma_pagamento,
                    'status' => $movimentacao->status,
                    'observacao' => $movimentacao->observacao,
                    'parcelado' => $movimentacao->parcelado,
                    'parcela_atual' => $movimentacao->parcela_atual,
                    'total_parcelas' => $movimentacao->total_parcelas,
                    'grupo_parcelamento' => $movimentacao->grupo_parcelamento,

                    'fixo_mensal' => $movimentacao->fixo_mensal,
                    'mes_atual' => $movimentacao->mes_atual,
                    'total_meses' => $movimentacao->total_meses,
                    'grupo_fixo_mensal' => $movimentacao->grupo_fixo_mensal,
                ];
            });

        $totalEntradas = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'entrada')
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->sum('valor');

        $totalDespesas = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->sum('valor');

        $totalPendentes = Movimentacao::where('user_id', Auth::id())
            ->where('tipo', 'despesa')
            ->where('status', 'pendente')
            ->whereBetween('data', [$inicioDoMes, $fimDoMes])
            ->sum('valor');

        $saldo = $totalEntradas - $totalDespesas;

        return Inertia::render('movimentacoes/index', [
            'movimentacoes' => $movimentacoes,
            'resumo' => [
                'entradas' => $totalEntradas,
                'despesas' => $totalDespesas,
                'saldo' => $saldo,
                'pendentes' => $totalPendentes,
            ],
            'filtros' => [
                'mes' => $mesSelecionado,
                'tipo' => $tipoSelecionado,
                'status' => $statusSelecionado,
            ],
        ]);
    }

    public function create(): Response
    {
        $categorias = Categoria::where('user_id', Auth::id())
            ->where('ativo', true)
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'tipo' => $categoria->tipo,
                    'nome' => $categoria->nome,
                ];
            });

        $formasPagamento = FormaPagamento::where('user_id', Auth::id())
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(function ($formaPagamento) {
                return [
                    'id' => $formaPagamento->id,
                    'nome' => $formaPagamento->nome,
                ];
            });

        return Inertia::render('movimentacoes/create', [
            'categorias' => $categorias,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mensagens = [
            'tipo.required' => 'Informe o tipo da movimentação.',
            'tipo.in' => 'O tipo deve ser entrada ou despesa.',

            'descricao.required' => 'Informe a descrição.',
            'descricao.string' => 'A descrição deve ser um texto.',
            'descricao.max' => 'A descrição não pode ter mais que 255 caracteres.',

            'valor.required' => 'Informe o valor.',
            'valor.numeric' => 'Informe um valor válido.',
            'valor.min' => 'O valor deve ser maior que zero.',

            'data.required' => 'Informe a data.',
            'data.date' => 'Informe uma data válida.',

            'categoria.string' => 'A categoria deve ser um texto.',
            'categoria.max' => 'A categoria não pode ter mais que 255 caracteres.',

            'forma_pagamento.string' => 'A forma de pagamento deve ser um texto.',
            'forma_pagamento.max' => 'A forma de pagamento não pode ter mais que 255 caracteres.',

            'status.required' => 'Informe o status.',
            'status.in' => 'Informe um status válido.',

            'observacao.string' => 'A observação deve ser um texto.',

            'parcelado.boolean' => 'O campo parcelado deve ser verdadeiro ou falso.',
            'parcela_fixa.boolean' => 'O campo parcela fixa deve ser verdadeiro ou falso.',

            'total_parcelas.integer' => 'A quantidade de parcelas deve ser um número inteiro.',
            'total_parcelas.min' => 'A quantidade de parcelas deve ser no mínimo 2.',
            'total_parcelas.max' => 'A quantidade de parcelas não pode ser maior que 120.',

            'fixo_mensal.boolean' => 'O campo entrada fixa mensal deve ser verdadeiro ou falso.',
            'total_meses.integer' => 'A quantidade de meses deve ser um número inteiro.',
            'total_meses.min' => 'A quantidade de meses deve ser no mínimo 2.',
            'total_meses.max' => 'A quantidade de meses não pode ser maior que 120.',
        ];

        $dados = $request->validate([
            'tipo' => ['required', 'in:entrada,despesa'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data' => ['required', 'date'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'forma_pagamento' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pago,pendente,recebido'],
            'observacao' => ['nullable', 'string'],

            'parcelado' => ['nullable', 'boolean'],
            'parcela_fixa' => ['nullable', 'boolean'],
            'total_parcelas' => ['nullable', 'integer', 'min:2', 'max:120'],

            'fixo_mensal' => ['nullable', 'boolean'],
            'total_meses' => ['nullable', 'integer', 'min:2', 'max:120'],
        ], $mensagens);

        $parcelado = $request->boolean('parcelado');
        $parcelaFixa = $request->boolean('parcela_fixa');

        $fixoMensal = $request->boolean('fixo_mensal');

        if ($parcelado && $dados['tipo'] !== 'despesa') {
            return back()
                ->withErrors([
                    'parcelado' => 'O parcelamento está disponível apenas para despesas.',
                ])
                ->withInput();
        }

        if ($fixoMensal && $dados['tipo'] !== 'entrada') {
            return back()
                ->withErrors([
                    'fixo_mensal' => 'Entrada fixa mensal está disponível apenas para entradas.',
                ])
                ->withInput();
        }

        if ($parcelado && empty($dados['total_parcelas'])) {
            return back()
                ->withErrors([
                    'total_parcelas' => 'Informe a quantidade de parcelas.',
                ])
                ->withInput();
        }

        if ($fixoMensal && empty($dados['total_meses'])) {
            return back()
                ->withErrors([
                    'total_meses' => 'Informe a quantidade de meses.',
                ])
                ->withInput();
        }

        /*
        --------------------------------------------------------------------------
        | ENTRADA FIXA MENSAL
        --------------------------------------------------------------------------
        */
        if ($dados['tipo'] === 'entrada' && $fixoMensal) {
            $totalMeses = (int) $dados['total_meses'];
            $grupoFixoMensal = (string) Str::uuid();

            $dataPrimeiroMes = \Carbon\Carbon::parse($dados['data']);
            $valorMensal = (float) $dados['valor'];

            for ($mes = 1; $mes <= $totalMeses; $mes++) {
                Movimentacao::create([
                    'user_id' => Auth::id(),
                    'tipo' => 'entrada',
                    'descricao' => $dados['descricao'] . ' - Mensal ' . $mes . '/' . $totalMeses,
                    'valor' => $valorMensal,
                    'data' => $dataPrimeiroMes
                        ->copy()
                        ->addMonthsNoOverflow($mes - 1)
                        ->format('Y-m-d'),
                    'categoria' => $dados['categoria'] ?? null,
                    'forma_pagamento' => null,
                    'status' => 'recebido',
                    'observacao' => $dados['observacao'] ?? null,

                    'parcelado' => false,
                    'parcela_fixa' => false,
                    'fixo_mensal' => true,

                    'mes_atual' => $mes,
                    'total_meses' => $totalMeses,

                    'parcela_atual' => null,
                    'total_parcelas' => null,

                    'grupo_parcelamento' => null,
                    'grupo_fixo_mensal' => $grupoFixoMensal,

                    'data_pagamento' => null,
                ]);
            }

            return redirect()
                ->route('movimentacoes.index')
                ->with('success', 'Entrada fixa mensal cadastrada com sucesso.');
        }

        $dataPagamento = null;

        if ($dados['tipo'] === 'despesa' && $dados['status'] === 'pago') {
            $dataPagamento = now()->toDateString();
        }

        /*--------------------------------------------------------------------------
        | MOVIMENTAÇÃO NORMAL
        --------------------------------------------------------------------------
        */
        if (!$parcelado) {
            Movimentacao::create([
                'user_id' => Auth::id(),
                'tipo' => $dados['tipo'],
                'descricao' => $dados['descricao'],
                'valor' => $dados['valor'],
                'data' => $dados['data'],
                'categoria' => $dados['categoria'] ?? null,
                'forma_pagamento' => $dados['tipo'] === 'entrada'
                    ? null
                    : ($dados['forma_pagamento'] ?? null),
                'status' => $dados['tipo'] === 'entrada'
                    ? 'recebido'
                    : $dados['status'],
                'observacao' => $dados['observacao'] ?? null,

                'parcelado' => false,
                'parcela_fixa' => false,
                'fixo_mensal' => false,

                'mes_atual' => null,
                'total_meses' => null,

                'parcela_atual' => null,
                'total_parcelas' => null,

                'grupo_parcelamento' => null,
                'grupo_fixo_mensal' => null,

                'data_pagamento' => $dataPagamento,
            ]);

            return redirect()
                ->route('movimentacoes.index')
                ->with('success', 'Movimentação cadastrada com sucesso.');
        }

        /*
        --------------------------------------------------------------------------
        | DESPESA PARCELADA
        --------------------------------------------------------------------------
        | O valor informado é o valor da parcela.
        | Não divide automaticamente.
        */
        $totalParcelas = (int) $dados['total_parcelas'];
        $grupoParcelamento = (string) Str::uuid();

        $valorParcela = (float) $dados['valor'];
        $dataPrimeiraParcela = \Carbon\Carbon::parse($dados['data']);

        for ($parcela = 1; $parcela <= $totalParcelas; $parcela++) {
            $descricaoParcela = $parcelaFixa
                ? $dados['descricao'] . ' - Mensal ' . $parcela . '/' . $totalParcelas
                : $dados['descricao'] . ' - Parcela ' . $parcela . '/' . $totalParcelas;

            Movimentacao::create([
                'user_id' => Auth::id(),
                'tipo' => 'despesa',
                'descricao' => $descricaoParcela,
                'valor' => $valorParcela,
                'data' => $dataPrimeiraParcela
                    ->copy()
                    ->addMonthsNoOverflow($parcela - 1)
                    ->format('Y-m-d'),
                'categoria' => $dados['categoria'] ?? null,
                'forma_pagamento' => $dados['forma_pagamento'] ?? null,
                'status' => 'pendente',
                'observacao' => $dados['observacao'] ?? null,

                'parcelado' => true,
                'parcela_fixa' => $parcelaFixa,
                'fixo_mensal' => false,

                'mes_atual' => null,
                'total_meses' => null,

                'parcela_atual' => $parcela,
                'total_parcelas' => $totalParcelas,

                'grupo_parcelamento' => $grupoParcelamento,
                'grupo_fixo_mensal' => null,

                'data_pagamento' => null,
            ]);
        }

        return redirect()
            ->route('movimentacoes.index')
            ->with('success', 'Compra parcelada cadastrada com sucesso.');
    }

    public function edit(string $id): Response
    {
        $movimentacao = Movimentacao::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $categorias = Categoria::where('user_id', Auth::id())
            ->where('ativo', true)
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'tipo' => $categoria->tipo,
                    'nome' => $categoria->nome,
                ];
            });

        $formasPagamento = FormaPagamento::where('user_id', Auth::id())
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(function ($formaPagamento) {
                return [
                    'id' => $formaPagamento->id,
                    'nome' => $formaPagamento->nome,
                ];
            });

        return Inertia::render('movimentacoes/edit', [
            'movimentacao' => [
            'id' => $movimentacao->id,
            'tipo' => $movimentacao->tipo,
            'descricao' => $movimentacao->descricao,
            'valor' => $movimentacao->valor,
            'data' => $movimentacao->data->format('Y-m-d'),
            'categoria' => $movimentacao->categoria,
            'forma_pagamento' => $movimentacao->forma_pagamento,
            'status' => $movimentacao->status,
            'observacao' => $movimentacao->observacao,

            'parcelado' => $movimentacao->parcelado,
            'parcela_atual' => $movimentacao->parcela_atual,
            'total_parcelas' => $movimentacao->total_parcelas,
            'grupo_parcelamento' => $movimentacao->grupo_parcelamento,

            'fixo_mensal' => $movimentacao->fixo_mensal,
            'mes_atual' => $movimentacao->mes_atual,
            'total_meses' => $movimentacao->total_meses,
            'grupo_fixo_mensal' => $movimentacao->grupo_fixo_mensal,
        ],
            'categorias' => $categorias,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $movimentacao = Movimentacao::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $mensagens = [
        'tipo.required' => 'Informe o tipo da movimentação.',
        'tipo.in' => 'O tipo deve ser entrada ou despesa.',

        'descricao.required' => 'Informe a descrição.',
        'descricao.string' => 'A descrição deve ser um texto.',
        'descricao.max' => 'A descrição não pode ter mais que 255 caracteres.',

        'valor.required' => 'Informe o valor.',
        'valor.numeric' => 'Informe um valor válido.',
        'valor.min' => 'O valor deve ser maior que zero.',

        'data.required' => 'Informe a data.',
        'data.date' => 'Informe uma data válida.',

        'categoria.string' => 'A categoria deve ser um texto.',
        'categoria.max' => 'A categoria não pode ter mais que 255 caracteres.',

        'forma_pagamento.string' => 'A forma de pagamento deve ser um texto.',
        'forma_pagamento.max' => 'A forma de pagamento não pode ter mais que 255 caracteres.',

        'status.required' => 'Informe o status.',
        'status.in' => 'Informe um status válido.',

        'observacao.string' => 'A observação deve ser um texto.',

        'modo_edicao.in' => 'Informe uma opção válida para edição.',
    ];

    $dados = $request->validate([
        'tipo' => ['required', 'in:entrada,despesa'],
        'descricao' => ['required', 'string', 'max:255'],
        'valor' => ['required', 'numeric', 'min:0.01'],
        'data' => ['required', 'date'],
        'categoria' => ['nullable', 'string', 'max:255'],
        'forma_pagamento' => ['nullable', 'string', 'max:255'],
        'status' => ['required', 'in:pago,pendente,recebido'],
        'observacao' => ['nullable', 'string'],
        'modo_edicao' => ['nullable', 'in:atual,todos_fixo'],
    ], $mensagens);

    $modoEdicao = $request->input('modo_edicao', 'atual');

    if ($movimentacao->fixo_mensal && $modoEdicao === 'todos_fixo') {
        $descricaoBase = preg_replace(
            '/\s-\sMensal\s\d+\/\d+$/',
            '',
            $dados['descricao']
        );

        $mesAtual = $movimentacao->mes_atual ?? 1;

        $dataBase = \Carbon\Carbon::parse($dados['data'])
            ->subMonthsNoOverflow($mesAtual - 1);

        $movimentacoesDoGrupo = Movimentacao::where('user_id', Auth::id())
            ->where('grupo_fixo_mensal', $movimentacao->grupo_fixo_mensal)
            ->orderBy('mes_atual')
            ->get();

        foreach ($movimentacoesDoGrupo as $item) {
            $mesItem = $item->mes_atual ?? 1;
            $totalMeses = $item->total_meses ?? $movimentacao->total_meses;

            $item->update([
                'tipo' => 'entrada',
                'descricao' => $descricaoBase . ' - Mensal ' . $mesItem . '/' . $totalMeses,
                'valor' => $dados['valor'],
                'data' => $dataBase
                    ->copy()
                    ->addMonthsNoOverflow($mesItem - 1)
                    ->format('Y-m-d'),
                'categoria' => $dados['categoria'] ?? null,
                'forma_pagamento' => null,
                'status' => 'recebido',
                'observacao' => $dados['observacao'] ?? null,
                'data_pagamento' => null,
            ]);
        }

        return redirect()
            ->route('movimentacoes.index')
            ->with('success', 'Entrada fixa mensal atualizada em todos os meses.');
}

        if ($dados['tipo'] === 'despesa') {
            if ($dados['status'] === 'pago') {
                $dados['data_pagamento'] = $movimentacao->data_pagamento
                    ? $movimentacao->data_pagamento->format('Y-m-d')
                    : now()->toDateString();
            }

            if ($dados['status'] === 'pendente') {
                $dados['data_pagamento'] = null;
            }
        }

        if ($dados['tipo'] === 'entrada') {
            $dados['status'] = 'recebido';
            $dados['forma_pagamento'] = null;
            $dados['data_pagamento'] = null;
        }

        $movimentacao->update($dados);

        return redirect()
            ->route('movimentacoes.index')
            ->with('success', 'Movimentação atualizada com sucesso.');
    }

    public function marcarComoPago(string $id): RedirectResponse
    {
        $movimentacao = Movimentacao::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if ($movimentacao->tipo !== 'despesa') {
            return back()
                ->withErrors([
                    'status' => 'Somente despesas podem ser marcadas como pagas.',
                ]);
        }

        $movimentacao->update([
            'status' => 'pago',
            'data_pagamento' => now()->toDateString(),
        ]);

        return back()
            ->with('success', 'Parcela marcada como paga com sucesso.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $movimentacao = Movimentacao::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$movimentacao) {
            return redirect()
                ->route('movimentacoes.index')
                ->with('success', 'Esta movimentação já foi excluída ou não está mais disponível.');
        }

        $modoExclusao = $request->input('modo_exclusao', 'atual');

        /*

        >>> ENTRADA FIXA MENSAL

        | atual = exclui somente este mês
        | todos_fixo = exclui todos os meses do mesmo grupo
        */
        if ($movimentacao->fixo_mensal) {
            if ($modoExclusao === 'todos_fixo') {
                $quantidadeExcluida = Movimentacao::where('user_id', Auth::id())
                    ->where('grupo_fixo_mensal', $movimentacao->grupo_fixo_mensal)
                    ->delete();

                return redirect()
                    ->route('movimentacoes.index')
                    ->with('success', "{$quantidadeExcluida} lançamento(s) da entrada fixa mensal excluído(s) com sucesso.");
            }

            $movimentacao->delete();

            return redirect()
                ->route('movimentacoes.index')
                ->with('success', 'Lançamento deste mês excluído com sucesso.');
        }

        /*
        >>> MOVIMENTAÇÃO NORMAL
        */
        if (!$movimentacao->parcelado) {
            $movimentacao->delete();

            return redirect()
                ->route('movimentacoes.index')
                ->with('success', 'Movimentação excluída com sucesso.');
        }

        /*
        | DESPESA PARCELADA
        | futuras = exclui esta parcela e as próximas pendentes
        */
        if ($modoExclusao === 'futuras') {
            $quantidadeExcluida = Movimentacao::where('user_id', Auth::id())
                ->where('grupo_parcelamento', $movimentacao->grupo_parcelamento)
                ->where('parcela_atual', '>=', $movimentacao->parcela_atual)
                ->where('status', 'pendente')
                ->whereNull('data_pagamento')
                ->delete();

            if ($quantidadeExcluida === 0) {
                return back()
                    ->withErrors([
                        'exclusao' => 'Nenhuma parcela foi excluída. Parcelas pagas não podem ser apagadas.',
                    ]);
            }

            return redirect()
                ->route('movimentacoes.index')
                ->with('success', "{$quantidadeExcluida} parcela(s) excluída(s) com sucesso. Parcelas pagas foram mantidas.");
        }

        if ($movimentacao->status === 'pago' || $movimentacao->data_pagamento) {
            return back()
                ->withErrors([
                    'exclusao' => 'Esta parcela já foi paga e não pode ser excluída.',
                ]);
        }

        $movimentacao->delete();

        return redirect()
            ->route('movimentacoes.index')
            ->with('success', 'Parcela excluída com sucesso.');
    }
}
