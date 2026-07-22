import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Movimentacao = {
    id: number;
    tipo: string;
    descricao: string;
    valor: string | number;
    data: string;
    categoria: string | null;
    forma_pagamento: string | null;
    status: string;
    observacao: string | null;

    parcelado: boolean;
    parcela_fixa: boolean;
    despesa_fixa_id: number | null;
    entrada_fixa_id: number | null;
    parcela_atual: number | null;
    total_parcelas: number | null;
    grupo_parcelamento: string | null;

    fixo_mensal: boolean;
    mes_atual: number | null;
    total_meses: number | null;
    grupo_fixo_mensal: string | null;
};

type Categoria = {
    id: number;
    tipo: 'entrada' | 'despesa';
    nome: string;
};

type FormaPagamento = {
    id: number;
    nome: string;
}

type Props = {
    movimentacao: Movimentacao;
    categorias?: Categoria[];
    formasPagamento?: FormaPagamento[];
};

type FormData = {
    tipo: string;
    descricao: string;
    valor: string;
    data: string;
    categoria: string;
    forma_pagamento: string;
    status: string;
    observacao: string;
    total_parcelas: string;
    modo_edicao: 'atual' | 'todos_fixo' | 'futuros_fixa';
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: '/movimentacoes',
    },
    {
        title: 'Editar movimentação',
        href: '#',
    },
];

function formatarValorMonetario(valor: string): string {
    const somenteNumeros = valor.replace(/\D/g, '');

    if (!somenteNumeros) {
        return '';
    }

    const numero = Number(somenteNumeros) / 100;

    return numero.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function converterValorParaBanco(valor: string): string {
    const somenteNumeros = valor.replace(/\D/g, '');

    if (!somenteNumeros) {
        return '';
    }

    return (Number(somenteNumeros) / 100).toFixed(2);
}

export default function MovimentacoesEdit({
    movimentacao,
    categorias = [],
    formasPagamento = [],
}: Props) {
    const { data, setData, put, processing, errors, transform } = useForm<FormData>({
        tipo: movimentacao.tipo,
        descricao: movimentacao.descricao,
        valor: String(movimentacao.valor),
        data: movimentacao.data,
        categoria: movimentacao.categoria || '',
        forma_pagamento: movimentacao.forma_pagamento || '',
        status: movimentacao.status,
        observacao: movimentacao.observacao || '',
        total_parcelas: movimentacao.total_parcelas
            ? String(movimentacao.total_parcelas)
            : '',
        modo_edicao: 'atual',
    });

    const [modalEdicaoAberto, setModalEdicaoAberto] = useState(false);

    const categoriasDisponiveis = categorias.filter(
        (categoria) => categoria.tipo === data.tipo
    );

    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    const dataHoje = `${ano}-${mes}-${dia}`;

    const statusPendenteDespesa =
        data.data < dataHoje ? 'Vencido' : 'A vencer';

    function enviarFormulario(event: FormEvent) {
        event.preventDefault();

        if ( movimentacao.fixo_mensal ||
            (movimentacao.parcela_fixa && movimentacao.despesa_fixa_id)
        ) {
            setModalEdicaoAberto(true);
            return;
        }

        salvarMovimentacao('atual');
    }

    function salvarMovimentacao(
        modoEdicao: 'atual' | 'todos_fixo' | 'futuros_fixa',
    ) {
        transform((dados) => ({
            ...dados,
            modo_edicao: modoEdicao,
        }));

        put(`/movimentacoes/${movimentacao.id}`, {
            preserveScroll: true,

            onSuccess: () => {
                setModalEdicaoAberto(false);
            },

            onError: () => {
                setModalEdicaoAberto(false);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar movimentação" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Editar movimentação
                        </h1>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Altere os dados da entrada ou despesa.
                        </p>
                    </div>

                    <Link
                        href="/movimentacoes"
                        className="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Voltar
                    </Link>
                </div>

                <form
                    onSubmit={enviarFormulario}
                    className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div className="grid gap-5 md:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipo
                            </label>

                            <select
                                value={data.tipo}
                                disabled={
                                    movimentacao.parcelado ||
                                    movimentacao.fixo_mensal ||
                                    Boolean(
                                        movimentacao.parcela_fixa &&
                                            movimentacao.despesa_fixa_id
                                    )
                                }
                                onChange={(event) => {
                                    const novoTipo = event.target.value;

                                    setData({
                                        ...data,
                                        tipo: novoTipo,
                                        categoria: '',
                                        forma_pagamento: '',
                                        status:
                                            novoTipo === 'despesa'
                                                ? 'pendente'
                                                : 'recebido',
                                    });
                                }}
                                className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:disabled:bg-gray-800"
                            >
                                <option value="entrada">Entrada</option>
                                <option value="despesa">Despesa</option>
                            </select>

                            {errors.tipo && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.tipo}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Data
                            </label>

                            <input
                                type="date"
                                value={data.data}
                                onChange={(event) =>
                                    setData('data', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.data && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.data}
                                </p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Descrição
                            </label>

                            <input
                                type="text"
                                value={data.descricao}
                                onChange={(event) =>
                                    setData('descricao', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.descricao && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.descricao}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Valor
                            </label>

                            <input
                                type="text"
                                inputMode="numeric"
                                value={formatarValorMonetario(data.valor)}
                                onChange={(event) =>
                                    setData(
                                        'valor',
                                        converterValorParaBanco(event.target.value)
                                    )
                                }
                                placeholder="0,00"
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.valor && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.valor}
                                </p>
                            )}
                        </div>

                        {movimentacao.parcelado && (
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Quantidade de parcelas
                                </label>

                                <input
                                    type="number"
                                    min="2"
                                    max="120"
                                    value={data.total_parcelas}
                                    onChange={(event) =>
                                        setData('total_parcelas', event.target.value)
                                    }
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                />

                                {errors.total_parcelas && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.total_parcelas}
                                    </p>
                                )}

                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Esta compra está na parcela{' '}
                                    {movimentacao.parcela_atual || 1} de{' '}
                                    {movimentacao.total_parcelas || 0}. Você pode aumentar ou
                                    reduzir a quantidade total.
                                </p>
                            </div>
                        )}

                        {movimentacao.fixo_mensal && (
                        <div className="md:col-span-2 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                            <p className="text-sm font-semibold text-green-800 dark:text-green-200">
                                Esta entrada é fixa todos os meses
                            </p>

                            <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                {movimentacao.entrada_fixa_id
                                    ? 'Ao salvar, você poderá alterar somente este mês ou este mês e os próximos lançamentos.'
                                    : 'Esta entrada utiliza o modelo mensal antigo e continuará preservada.'}
                            </p>
                        </div>
                    )}

                        {movimentacao.parcela_fixa &&
                        movimentacao.despesa_fixa_id && (
                            <div className="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                                <p className="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                    Esta despesa é fixa todos os meses
                                </p>

                                <p className="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                    Ao salvar, você poderá alterar somente este mês ou este mês e os próximos lançamentos.
                                </p>
                            </div>
                        )}

                        {data.tipo === 'despesa' && (
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>

                                <select
                                    value={data.status}
                                    onChange={(event) =>
                                        setData('status', event.target.value)
                                    }
                                    className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                                    <option value="pago">Pago</option>
                                    <option value="pendente">{statusPendenteDespesa}</option>
                                </select>

                                {errors.status && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.status}
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Categoria
                            </label>

                            <select
                                value={data.categoria}
                                onChange={(event) =>
                                    setData('categoria', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">Selecione uma categoria</option>

                                {data.categoria &&
                                    !categoriasDisponiveis.some(
                                        (categoria) =>
                                            categoria.nome === data.categoria
                                    ) && (
                                        <option value={data.categoria}>
                                            {data.categoria}
                                        </option>
                                    )}

                                {categoriasDisponiveis.length === 0 && (
                                    <option value="" disabled>
                                        Nenhuma categoria cadastrada para este tipo
                                    </option>
                                )}

                                {categoriasDisponiveis.map((categoria) => (
                                    <option
                                        key={categoria.id}
                                        value={categoria.nome}
                                    >
                                        {categoria.nome}
                                    </option>
                                ))}
                            </select>

                            {errors.categoria && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.categoria}
                                </p>
                            )}
                        </div>

                        {data.tipo === 'despesa' && (
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Forma de pagamento
                                </label>

                                <select
                                    value={data.forma_pagamento}
                                    onChange={(event) =>
                                        setData(
                                            'forma_pagamento',
                                            event.target.value
                                        )
                                    }
                                    className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                                    <option value="">
                                        Selecione a forma de pagamento
                                    </option>

                                    {data.forma_pagamento &&
                                        !formasPagamento.some(
                                            (forma) => forma.nome === data.forma_pagamento
                                        ) && (
                                            <option value={data.forma_pagamento}>
                                                {data.forma_pagamento}
                                            </option>
                                        )}

                                    {formasPagamento.length === 0 && (
                                        <option value="" disabled>
                                            Nenhuma forma de pagamento cadastrada
                                        </option>
                                    )}

                                    {formasPagamento.map((forma) => (
                                        <option key={forma.id} value={forma.nome}>
                                            {forma.nome}
                                        </option>
                                    ))}
                                </select>

                                {errors.forma_pagamento && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.forma_pagamento}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Observação
                            </label>

                            <textarea
                                value={data.observacao}
                                onChange={(event) =>
                                    setData('observacao', event.target.value)
                                }
                                rows={4}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.observacao && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.observacao}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col gap-3 md:flex-row md:justify-end">
                        <Link
                            href="/movimentacoes"
                            className="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            Cancelar
                        </Link>

                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? 'Salvando...' : 'Salvar alterações'}
                        </button>
                    </div>
                                </form>

                {modalEdicaoAberto && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
                            <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-lg font-bold text-gray-900 dark:text-white">
                                            Confirmar edição
                                        </h2>

                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {data.descricao}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => setModalEdicaoAberto(false)}
                                        className="rounded-full px-2 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <div className="px-6 py-5">
                                {movimentacao.parcela_fixa &&
                                movimentacao.despesa_fixa_id ? (
                                    <>
                                        <div className="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                                            <p className="text-sm font-semibold text-blue-800 dark:text-blue-200">
                                                Esta despesa é fixa todos os meses
                                            </p>

                                            <p className="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                                Escolha se a alteração deverá valer somente para este mês ou também para os próximos lançamentos.
                                            </p>
                                        </div>

                                        {movimentacao.status === 'pago' && (
                                            <div className="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                                <p className="text-sm text-green-700 dark:text-green-300">
                                                    Este lançamento está marcado como pago. Se você mantiver
                                                    o status Pago, este mês será preservado no histórico e as
                                                    alterações começarão no próximo mês. Se mudar para A vencer,
                                                    este mês também será atualizado.
                                                </p>
                                            </div>
                                        )}

                                        <div className="space-y-3">
                                            <button
                                                type="button"
                                                onClick={() => salvarMovimentacao('atual')}
                                                disabled={processing}
                                                className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:-translate-y-0.5 hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-500 dark:hover:bg-gray-800"
                                            >
                                                Alterar somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Altera somente este lançamento. Os próximos meses manterão os dados atuais da despesa fixa.
                                                </span>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    salvarMovimentacao('futuros_fixa')
                                                }
                                                disabled={processing}
                                                className="w-full cursor-pointer rounded-xl border border-blue-300 bg-blue-50 px-4 py-3 text-left text-sm font-semibold text-blue-700 transition duration-200 hover:-translate-y-0.5 hover:border-blue-400 hover:bg-blue-100 hover:shadow-sm disabled:opacity-50 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300 dark:hover:border-blue-700 dark:hover:bg-blue-900"
                                            >
                                                Alterar este mês e os próximos

                                                <span className="mt-1 block text-xs font-normal text-blue-600 dark:text-blue-400">
                                                    Atualiza a regra mensal e os próximos lançamentos pendentes.
                                                    O mês selecionado respeitará o status escolhido acima.
                                                    Outros lançamentos pagos serão preservados.
                                                </span>
                                            </button>
                                        </div>
                                    </>
                                ) : (
                                        <>
                                        <div className="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                            <p className="text-sm font-semibold text-green-800 dark:text-green-200">
                                                Esta entrada é fixa todos os meses
                                            </p>

                                            <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                                {movimentacao.entrada_fixa_id
                                                    ? 'Escolha se a alteração deverá valer somente para este mês ou também para os próximos lançamentos.'
                                                    : 'Esta entrada pertence ao modelo mensal antigo. Os lançamentos já existentes serão preservados.'}
                                            </p>
                                        </div>

                                        <div className="space-y-3">
                                            <button
                                                type="button"
                                                onClick={() => salvarMovimentacao('atual')}
                                                disabled={processing}
                                                className="w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-4 py-3 text-left text-sm font-semibold text-gray-800 transition duration-200 hover:-translate-y-0.5 hover:border-green-400 hover:bg-green-50 hover:shadow-sm disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-green-500 dark:hover:bg-gray-800"
                                            >
                                                Alterar somente este mês

                                                <span className="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    Altera somente este lançamento. Os outros meses manterão os dados atuais.
                                                </span>
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    salvarMovimentacao(
                                                        movimentacao.entrada_fixa_id
                                                            ? 'futuros_fixa'
                                                            : 'todos_fixo',
                                                    )
                                                }
                                                disabled={processing}
                                                className="w-full cursor-pointer rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-left text-sm font-semibold text-green-700 transition duration-200 hover:-translate-y-0.5 hover:border-green-400 hover:bg-green-100 hover:shadow-sm disabled:opacity-50 dark:border-green-800 dark:bg-green-950 dark:text-green-300 dark:hover:border-green-700 dark:hover:bg-green-900"
                                            >
                                                {movimentacao.entrada_fixa_id
                                                    ? 'Alterar este mês e os próximos'
                                                    : 'Alterar os meses cadastrados'}

                                                <span className="mt-1 block text-xs font-normal text-green-600 dark:text-green-400">
                                                    {movimentacao.entrada_fixa_id
                                                        ? 'Atualiza a regra mensal e os próximos lançamentos. Os meses anteriores serão preservados.'
                                                        : 'Atualiza somente os lançamentos existentes do modelo antigo.'}
                                                </span>
                                            </button>
                                        </div>
                                    </>
                                )}
                            </div>

                            <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setModalEdicaoAberto(false)}
                                    className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
