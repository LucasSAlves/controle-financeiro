import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';

type Movimentacao = {
    id: number;
    tipo: 'entrada' | 'despesa';
    descricao: string;
    valor: string | number;
    data: string;
    data_pagamento: string | null;
    categoria: string | null;
    forma_pagamento: string | null;
    status: 'pago' | 'pendente' | 'recebido';
    observacao: string | null;
    parcelado?: boolean;
    parcela_fixa?: boolean;
    despesa_fixa_id?: number | null;
    entrada_fixa_id?: number | null;
    parcela_atual?: number | null;
    total_parcelas?: number | null;


    fixo_mensal: boolean;
    mes_atual: number | null;
    total_meses: number | null;
    grupo_fixo_mensal: string | null;
    grupo_parcelamento: string | null;
};

type Resumo = {
    entradas: string | number;
    despesas: string | number;
    saldo: string | number;
    pendentes: string | number;
};

type Filtros = {
    mes: string;
    tipo: string;
    status: string;
};

type Props = {
    movimentacoes?: Movimentacao[];
    resumo: Resumo;
    filtros: Filtros;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: '/movimentacoes',
    },
];

function formatarMoeda(valor: string | number) {
    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function formatarData(data: string) {
    return new Date(`${data}T00:00:00`).toLocaleDateString('pt-BR');
}

function obterMesLocalAtual(): string {
    const hoje = new Date();

    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');

    return `${ano}-${mes}`;
}

function textoStatus(status: Movimentacao['status']) {
    if (status === 'pago') {
        return 'Pago';
    }

    if (status === 'recebido') {
        return 'Recebido';
    }

    return 'Pendente';
}

function classeStatus(status: Movimentacao['status']) {
    if (status === 'pago') {
        return 'rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700';
    }

    if (status === 'recebido') {
        return 'rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700';
    }

    return 'rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700';
}

export default function MovimentacoesIndex({
    movimentacoes = [],
    resumo = {
        entradas: 0,
        despesas: 0,
        saldo: 0,
        pendentes: 0,
    },
    filtros = {
        mes: obterMesLocalAtual(),
        tipo: 'todos',
        status: 'todos',
    },
}: Props) {
    const [modalExclusaoAberto, setModalExclusaoAberto] = useState(false);
    const [movimentacaoParaExcluir, setMovimentacaoParaExcluir] =
        useState<Movimentacao | null>(null);
    const [erroExclusao, setErroExclusao] = useState('');

    const [movimentacaoParaPagar, setMovimentacaoParaPagar] =
        useState<Movimentacao | null>(null);

    const [marcandoComoPago, setMarcandoComoPago] =
        useState(false);

    function excluirMovimentacao(movimentacao: Movimentacao) {
        setErroExclusao('');
        setMovimentacaoParaExcluir(movimentacao);
        setModalExclusaoAberto(true);

        if (
            movimentacao.parcelado &&
            (movimentacao.status === 'pago' || movimentacao.data_pagamento)
        ) {
            setErroExclusao('Esta parcela já foi paga e não pode ser excluída.');
        }
    }

    function fecharModalExclusao() {
        setModalExclusaoAberto(false);
        setMovimentacaoParaExcluir(null);
        setErroExclusao('');
    }

    function confirmarExclusao(
        modoExclusao:
            | 'atual'
            | 'futuras'
            | 'todos_fixo'
            | 'encerrar_fixa',
    ) {
        if (!movimentacaoParaExcluir) {
            return;
        }

        router.delete(`/movimentacoes/${movimentacaoParaExcluir.id}`, {
            data: {
                modo_exclusao: modoExclusao,
            },
            preserveScroll: true,

            onSuccess: () => {
                fecharModalExclusao();
            },

            onError: (errors) => {
                setErroExclusao(
                    typeof errors.exclusao === 'string'
                        ? errors.exclusao
                        : 'Não foi possível concluir a exclusão.',
                );
            },
        });
    }

    function marcarComoPago(movimentacao: Movimentacao) {
        setMovimentacaoParaPagar(movimentacao);
    }

    function confirmarPagamento() {
        if (!movimentacaoParaPagar) {
            return;
        }

        setMarcandoComoPago(true);

        router.patch(
            `/movimentacoes/${movimentacaoParaPagar.id}/marcar-como-pago`,
            {},
            {
                preserveScroll: true,

                onSuccess: () => {
                    setMovimentacaoParaPagar(null);
                },

                onFinish: () => {
                    setMarcandoComoPago(false);
                },
            },
        );
    }

    function aplicarFiltros(novosFiltros: Partial<Filtros>) {
        router.get(
            '/movimentacoes',
            {
                ...filtros,
                ...novosFiltros,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Movimentações" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Movimentações financeiras
                        </h1>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Controle suas entradas, despesas e saldo.
                        </p>
                    </div>

                    <div className="grid gap-3 md:grid-cols-3">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Filtrar por mês
                            </label>

                            <input
                                type="month"
                                value={filtros.mes}
                                onChange={(event) =>
                                    aplicarFiltros({ mes: event.target.value })
                                }
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipo
                            </label>

                            <select
                                value={filtros.tipo}
                                onChange={(event) =>
                                    aplicarFiltros({ tipo: event.target.value })
                                }
                                className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="todos">Todos</option>
                                <option value="entrada">Entradas</option>
                                <option value="despesa">Despesas</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status
                            </label>

                            <select
                                value={filtros.status}
                                onChange={(event) =>
                                    aplicarFiltros({ status: event.target.value })
                                }
                                className="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="todos">Todos</option>
                                <option value="pendente">Pendentes</option>
                                <option value="pago">Pagas</option>
                                <option value="recebido">Recebidas</option>
                            </select>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <Link
                            href="/movimentacoes/create?tipo=entrada"
                            className="rounded-lg bg-green-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-green-700"
                        >
                            Nova Entrada
                        </Link>

                        <Link
                            href="/movimentacoes/create?tipo=despesa"
                            className="rounded-lg bg-red-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-red-700"
                        >
                            Nova Despesa
                        </Link>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Total de entradas
                        </p>

                        <h2 className="mt-2 text-2xl font-bold text-green-600">
                            {formatarMoeda(resumo.entradas)}
                        </h2>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Total de despesas
                        </p>

                        <h2 className="mt-2 text-2xl font-bold text-red-600">
                            {formatarMoeda(resumo.despesas)}
                        </h2>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Total pendente
                        </p>

                        <h2 className="mt-2 text-2xl font-bold text-yellow-600">
                            {formatarMoeda(resumo.pendentes)}
                        </h2>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Saldo
                        </p>

                        <h2 className="mt-2 text-2xl font-bold text-blue-600">
                            {formatarMoeda(resumo.saldo)}
                        </h2>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Lançamentos cadastrados
                        </h2>
                    </div>

                    {movimentacoes.length === 0 ? (
                        <div className="p-8 text-center">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Nenhuma movimentação cadastrada ainda.
                            </p>

                            <Link
                                href="/movimentacoes/create"
                                className="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Cadastrar primeira movimentação
                            </Link>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <tr>
                                        <th className="px-5 py-3">Data</th>
                                        <th className="px-5 py-3">Tipo</th>
                                        <th className="px-5 py-3">Descrição</th>
                                        <th className="px-5 py-3">Categoria</th>
                                        <th className="px-5 py-3">Pagamento</th>
                                        <th className="px-5 py-3">Status</th>
                                        <th className="px-5 py-3 text-right">
                                            Valor
                                        </th>
                                        <th className="px-5 py-3 text-right">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {movimentacoes.map((movimentacao) => (
                                        <tr key={movimentacao.id}>
                                            <td className="px-5 py-4 text-gray-700 dark:text-gray-300">
                                                <p>
                                                    {formatarData(movimentacao.data)}
                                                </p>

                                                {movimentacao.data_pagamento && (
                                                    <p className="mt-1 text-xs text-green-600">
                                                        Pago em: {formatarData(movimentacao.data_pagamento)}
                                                    </p>
                                                )}
                                            </td>

                                            <td className="px-5 py-4">
                                                <span
                                                    className={
                                                        movimentacao.tipo ===
                                                        'entrada'
                                                            ? 'rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700'
                                                            : 'rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700'
                                                    }
                                                >
                                                    {movimentacao.tipo ===
                                                    'entrada'
                                                        ? 'Entrada'
                                                        : 'Despesa'}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4">
                                                <p className="font-medium text-gray-900 dark:text-white">
                                                    {movimentacao.descricao}
                                                </p>

                                                {movimentacao.parcelado &&
                                                    movimentacao.parcela_atual &&
                                                    movimentacao.total_parcelas && (
                                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            Parcela {movimentacao.parcela_atual}/
                                                            {movimentacao.total_parcelas}
                                                        </p>
                                                    )}

                                                {movimentacao.parcela_fixa && (
                                                    <p className="mt-1 text-xs font-medium text-blue-600 dark:text-blue-400">
                                                        Despesa fixa
                                                    </p>
                                                )}

                                                {movimentacao.fixo_mensal && (
                                                    <p className="mt-1 text-xs font-medium text-green-600 dark:text-green-400">
                                                        Entrada fixa
                                                    </p>
                                                )}
                                            </td>

                                            <td className="px-5 py-4 text-gray-700 dark:text-gray-300">
                                                {movimentacao.categoria || '-'}
                                            </td>

                                            <td className="px-5 py-4 text-gray-700 dark:text-gray-300">
                                                {movimentacao.forma_pagamento ||
                                                    '-'}
                                            </td>

                                            <td className="px-5 py-4">
                                                <span
                                                    className={classeStatus(
                                                        movimentacao.status
                                                    )}
                                                >
                                                    {textoStatus(
                                                        movimentacao.status
                                                    )}
                                                </span>
                                            </td>

                                            <td
                                                className={
                                                    movimentacao.tipo ===
                                                    'entrada'
                                                        ? 'px-5 py-4 text-right font-bold text-green-600'
                                                        : 'px-5 py-4 text-right font-bold text-red-600'
                                                }
                                            >
                                                {formatarMoeda(
                                                    movimentacao.valor
                                                )}
                                            </td>

                                            <td className="px-5 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    {movimentacao.tipo ===
                                                        'despesa' &&
                                                        movimentacao.status ===
                                                            'pendente' && (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    marcarComoPago(
                                                                        movimentacao
                                                                    )
                                                                }
                                                                className="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                                            >
                                                                Marcar como pago
                                                            </button>
                                                        )}

                                                    <Link
                                                        href={`/movimentacoes/${movimentacao.id}/edit`}
                                                        className="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                                    >
                                                        Editar
                                                    </Link>

                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            excluirMovimentacao(movimentacao)
                                                        }
                                                        className="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                                    >
                                                        Excluir
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
            {movimentacaoParaPagar && (
                <ConfirmDialog
                    open
                    title="Marcar como pago"
                    description={`Confirma o pagamento da movimentação "${movimentacaoParaPagar.descricao}" no valor de ${formatarMoeda(movimentacaoParaPagar.valor)}?`}
                    confirmLabel="Marcar como pago"
                    variant="info"
                    processing={marcandoComoPago}
                    onConfirm={confirmarPagamento}
                    onClose={() => setMovimentacaoParaPagar(null)}
                />
            )}

            {modalExclusaoAberto && movimentacaoParaExcluir && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                                        Confirmar exclusão
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {movimentacaoParaExcluir.descricao}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={fecharModalExclusao}
                                    className="rounded-full px-2 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                >
                                    ×
                                </button>
                            </div>
                        </div>

                        <div className="px-6 py-5">
                            {erroExclusao ? (
                                <div className="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                                    <p className="text-sm font-semibold text-red-700 dark:text-red-300">
                                        Não foi possível excluir
                                    </p>

                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {erroExclusao}
                                    </p>
                                </div>
                                ) :  movimentacaoParaExcluir.fixo_mensal ? (
                                movimentacaoParaExcluir.entrada_fixa_id ? (
                                    <>
                                        <div className="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                            <p className="text-sm font-semibold text-green-800 dark:text-green-200">
                                                Esta entrada é fixa todos os meses
                                            </p>

                                            <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                                Você pode excluir somente este mês ou encerrar a entrada fixa deste mês em diante.
                                            </p>

                                            <p className="mt-2 text-xs text-green-700 dark:text-green-300">
                                                Os lançamentos dos meses anteriores continuarão preservados no histórico.
                                            </p>
                                        </div>

                                        <div className="space-y-3">
                                            <button
                                                type="button"
                                                onClick={() => confirmarExclusao('atual')}
                                                className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:-translate-y-0.5 hover:border-green-400 hover:bg-green-50 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-green-500 dark:hover:bg-gray-800"
                                            >
                                                Excluir somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Remove somente este lançamento. A entrada continuará aparecendo nos próximos meses.
                                                </span>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    confirmarExclusao('encerrar_fixa')
                                                }
                                                className="w-full cursor-pointer rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition duration-200 hover:-translate-y-0.5 hover:border-red-400 hover:bg-red-100 hover:shadow-sm dark:border-red-800 dark:bg-red-950 dark:text-red-300 dark:hover:border-red-700 dark:hover:bg-red-900"
                                            >
                                                Encerrar deste mês em diante

                                                <span className="mt-1 block text-xs font-normal text-red-600 dark:text-red-400">
                                                    Encerra a recorrência e remove este mês e os próximos lançamentos já gerados. Os meses anteriores serão mantidos.
                                                </span>
                                            </button>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                                            <p className="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                                                Esta entrada utiliza o modelo mensal antigo
                                            </p>

                                            <p className="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                                Você pode excluir somente este lançamento ou todos os meses que foram cadastrados anteriormente.
                                            </p>
                                        </div>

                                        <div className="space-y-3">
                                            <button
                                                type="button"
                                                onClick={() => confirmarExclusao('atual')}
                                                className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:-translate-y-0.5 hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-500 dark:hover:bg-gray-800"
                                            >
                                                Excluir somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Remove apenas este lançamento mensal.
                                                </span>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    confirmarExclusao('todos_fixo')
                                                }
                                                className="w-full cursor-pointer rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition duration-200 hover:-translate-y-0.5 hover:border-red-400 hover:bg-red-100 hover:shadow-sm dark:border-red-800 dark:bg-red-950 dark:text-red-300 dark:hover:border-red-700 dark:hover:bg-red-900"
                                            >
                                                Excluir os meses cadastrados

                                                <span className="mt-1 block text-xs font-normal text-red-600 dark:text-red-400">
                                                    Remove os lançamentos pertencentes ao modelo antigo desta entrada mensal.
                                                </span>
                                            </button>
                                        </div>
                                    </>
                                )
                            ) : movimentacaoParaExcluir.parcela_fixa &&
                            movimentacaoParaExcluir.despesa_fixa_id ? (
                                <>
                                    <div className="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                                        <p className="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                            Esta despesa é fixa todos os meses
                                        </p>

                                        <p className="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                            Você pode excluir somente o lançamento deste mês ou encerrar a despesa fixa deste mês em diante.
                                        </p>
                                    </div>

                                    <div className="space-y-3">
                                        {movimentacaoParaExcluir.status === 'pago' ||
                                        movimentacaoParaExcluir.data_pagamento ? (
                                            <div className="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                                <p className="text-sm font-semibold text-green-800 dark:text-green-200">
                                                    Este lançamento já foi pago
                                                </p>

                                                <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                                    Ele será mantido no histórico. Ainda é possível encerrar os próximos lançamentos desta despesa fixa.
                                                </p>
                                            </div>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => confirmarExclusao('atual')}
                                                className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:-translate-y-0.5 hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-500 dark:hover:bg-gray-800"
                                            >
                                                Excluir somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Remove somente este lançamento. A despesa continuará aparecendo nos próximos meses.
                                                </span>
                                            </button>
                                        )}

                                        <button
                                            type="button"
                                            onClick={() => confirmarExclusao('encerrar_fixa')}
                                            className="w-full cursor-pointer rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition duration-200 hover:-translate-y-0.5 hover:border-red-400 hover:bg-red-100 hover:shadow-sm dark:border-red-800 dark:bg-red-950 dark:text-red-300 dark:hover:border-red-700 dark:hover:bg-red-900"
                                        >
                                            Encerrar deste mês em diante

                                            <span className="mt-1 block text-xs font-normal text-red-600 dark:text-red-400">
                                                Encerra a recorrência e remove os lançamentos pendentes deste mês e dos próximos. Pagamentos já realizados serão mantidos.
                                            </span>
                                        </button>
                                    </div>
                                </>
                            ) : movimentacaoParaExcluir.parcelado ? (
                                <>
                                    <div className="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                                        <p className="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                                            Esta despesa faz parte de um parcelamento
                                        </p>

                                        <p className="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                            Deseja excluir somente o lançamento deste mês ou também os próximos meses?
                                            Parcelas já pagas serão mantidas.
                                        </p>
                                    </div>

                                    <div className="space-y-3">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                confirmarExclusao('atual')
                                            }
                                            className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-500 dark:hover:bg-gray-800"
                                        >
                                            Excluir somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Remove apenas este lançamento mensal.
                                                </span>
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                confirmarExclusao('futuras')
                                            }
                                            className="w-full cursor-pointer rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-left text-sm font-semibold text-red-700 transition duration-200 hover:border-red-400 hover:bg-red-100 hover:shadow-sm dark:border-red-800 dark:bg-red-950 dark:text-red-300 dark:hover:border-red-700 dark:hover:bg-red-900"
                                        >
                                            Excluir este mês e os próximos

                                            <span className="mt-1 block text-xs font-normal text-red-600 dark:text-red-400">
                                                Remove este lançamento e os próximos meses ainda pendentes.
                                            </span>
                                        </button>
                                    </div>
                                </>
                            ) : (
                                <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                                    <p className="text-sm text-gray-700 dark:text-gray-300">
                                        Tem certeza que deseja excluir esta movimentação?
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            <button
                                type="button"
                                onClick={fecharModalExclusao}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancelar
                            </button>

                            {!erroExclusao &&
                                !movimentacaoParaExcluir.parcelado &&
                                !movimentacaoParaExcluir.parcela_fixa &&
                                !movimentacaoParaExcluir.fixo_mensal && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        confirmarExclusao('atual')
                                    }
                                    className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                >
                                    Excluir
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
