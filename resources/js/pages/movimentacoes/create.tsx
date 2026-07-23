import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type FormData = {
    tipo: string;
    descricao: string;
    valor: string;
    data: string;
    categoria: string;
    forma_pagamento: string;
    status: string;
    observacao: string;
    parcelado: boolean;
    parcela_fixa: boolean;
    total_parcelas: string;
    fixo_mensal: boolean;
};

type Categoria = {
    id: number;
    tipo: 'entrada' | 'despesa';
    nome: string;
};

type FormaPagamento = {
    id: number;
    nome: string;
};

type Props = {
    categorias?: Categoria[];
    formasPagamento?: FormaPagamento[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Movimentações',
        href: '/movimentacoes',
    },
    {
        title: 'Nova movimentação',
        href: '/movimentacoes/create',
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

function obterDataLocalHoje(): string {
    const hoje = new Date();

    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');

    return `${ano}-${mes}-${dia}`;
}

export default function MovimentacoesCreate({
    categorias = [],
    formasPagamento = [],
}: Props) {
    const params = new URLSearchParams(window.location.search);

    const tipoInicial = params.get('tipo') === 'despesa' ? 'despesa' : 'entrada';

    const dataHoje = obterDataLocalHoje();

    const { data, setData, post, processing, errors } = useForm<FormData>({
        tipo: tipoInicial,
        descricao: '',
        valor: '',
        data: dataHoje,
        categoria: '',
        forma_pagamento: '',
        status: tipoInicial === 'despesa' ? 'pendente' : 'recebido',
        observacao: '',
        parcelado: false,
        parcela_fixa: false,
        total_parcelas: '',
        fixo_mensal: false,
    });

    const categoriasDisponiveis = categorias.filter(
        (categoria) => categoria.tipo === data.tipo
    );

    const statusPendenteDespesa =
        data.data < dataHoje ? 'Vencido' : 'A vencer';

    function enviarFormulario(event: FormEvent) {
        event.preventDefault();

        post('/movimentacoes');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nova movimentação" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Nova movimentação
                        </h1>

                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Cadastre uma entrada ou despesa pessoal.
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
                                onChange={(event) => {
                                    const novoTipo = event.target.value;

                                    setData({
                                        ...data,
                                        tipo: novoTipo,
                                        categoria: '',
                                        status:
                                            novoTipo === 'despesa'
                                                ? 'pendente'
                                                : 'recebido',
                                        parcelado: false,
                                        parcela_fixa: false,
                                        total_parcelas: '',
                                        fixo_mensal: false,
                                        forma_pagamento: '',
                                    });
                                }}
                                className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
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
                                placeholder="Ex: Mercado, salário, aluguel, compra de carro"
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
                                {data.parcelado
                                    ? 'Valor da parcela'
                                    : data.parcela_fixa || data.fixo_mensal
                                        ? 'Valor mensal'
                                        : 'Valor'}
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

                            {data.parcelado && (
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Informe o valor de cada parcela. O sistema não dividirá o valor automaticamente.
                                </p>
                            )}

                            {data.parcela_fixa && (
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Informe o valor mensal desta despesa fixa.
                                </p>
                            )}

                            {data.fixo_mensal && (
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Informe o valor mensal desta entrada fixa.
                                </p>
                            )}
                        </div>

                        {data.tipo === 'despesa' && (
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>

                                <select
                                    value={data.status}
                                    disabled={data.parcelado || data.parcela_fixa}
                                    onChange={(event) =>
                                        setData('status', event.target.value)
                                    }
                                    className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:disabled:bg-gray-800"
                                >
                                    <option value="pago">Pago</option>
                                    <option value="pendente">{statusPendenteDespesa}</option>
                                </select>

                                {(data.parcelado || data.parcela_fixa) && (
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {data.parcela_fixa
                                            ? 'Despesas fixas mensais entram como pendentes.'
                                            : 'Compras parceladas entram como pendentes.'}
                                    </p>
                                )}

                                {errors.status && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {errors.status}
                                    </p>
                                )}
                            </div>
                        )}

                        {data.tipo === 'entrada' && (
                            <div className="md:col-span-2 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                <h2 className="mb-3 text-sm font-semibold text-green-900 dark:text-green-100">
                                    Entrada fixa mensal
                                </h2>

                                <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-green-300 bg-white p-3 text-sm dark:border-green-800 dark:bg-gray-900 dark:text-white">
                                    <input
                                        type="checkbox"
                                        checked={data.fixo_mensal}
                                        onChange={(event) =>
                                            setData('fixo_mensal', event.target.checked)
                                        }
                                    />

                                    Recebo este valor todos os meses
                                </label>

                                {errors.fixo_mensal && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors.fixo_mensal}
                                    </p>
                                )}

                                {data.fixo_mensal && (
                                    <div className="mt-4 rounded-lg border border-green-200 bg-white p-4 dark:border-green-800 dark:bg-gray-900">
                                        <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                            Esta entrada será lançada automaticamente todos os meses.
                                        </p>

                                        <p className="mt-1 text-xs text-green-700 dark:text-green-300">
                                            Não existe quantidade de meses. A entrada continuará ativa até ser encerrada.
                                        </p>

                                        <p className="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                            A data informada acima será usada como dia mensal de recebimento.
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        {data.tipo === 'despesa' && (
                            <div className="md:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                <h2 className="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Forma de lançamento
                                </h2>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <input
                                            type="radio"
                                            checked={!data.parcelado && !data.parcela_fixa}
                                            onChange={() =>
                                                setData({
                                                    ...data,
                                                    parcelado: false,
                                                    parcela_fixa: false,
                                                    total_parcelas: '',
                                                })
                                            }
                                        />

                                        À vista
                                    </label>

                                    <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <input
                                            type="radio"
                                            checked={data.parcelado}
                                            onChange={() =>
                                                setData({
                                                    ...data,
                                                    parcelado: true,
                                                    parcela_fixa: false,
                                                    total_parcelas: data.total_parcelas,
                                                    status: 'pendente',
                                                })
                                            }
                                        />

                                        Parcelado
                                    </label>

                                    <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                        <input
                                            type="radio"
                                            checked={data.parcela_fixa}
                                            onChange={() =>
                                                setData({
                                                    ...data,
                                                    parcelado: false,
                                                    parcela_fixa: true,
                                                    total_parcelas: '',
                                                    status: 'pendente',
                                                })
                                            }
                                        />

                                        Parcela fixa todos os meses
                                    </label>
                                </div>

                                {errors.parcelado && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors.parcelado}
                                    </p>
                                )}

                                {errors.parcela_fixa && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors.parcela_fixa}
                                    </p>
                                )}

                                {data.parcelado && (
                                    <div className="mt-4">
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
                                            placeholder="Ex: 12"
                                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        />

                                        {errors.total_parcelas && (
                                            <p className="mt-1 text-sm text-red-600">
                                                {errors.total_parcelas}
                                            </p>
                                        )}

                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            A data informada será a data da primeira parcela. As próximas serão criadas mês a mês.
                                        </p>
                                    </div>
                                )}

                                {data.parcela_fixa && (
                                    <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                                        <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                            Esta despesa será lançada automaticamente todos os meses.
                                        </p>

                                        <p className="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                            Não existe quantidade de parcelas. A recorrência continuará ativa até ser encerrada.
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Categoria
                            </label>

                            <select
                                required={data.tipo === 'despesa'}
                                value={data.categoria}
                                onChange={(event) =>
                                    setData('categoria', event.target.value)
                                }
                                className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="">Selecione uma categoria</option>

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
                                    required
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

                                    {formasPagamento.length === 0 && (
                                        <option value="" disabled>
                                            Nenhuma forma de pagamento cadastrada
                                        </option>
                                    )}

                                    {formasPagamento.map((formaPagamento) => (
                                        <option
                                            key={formaPagamento.id}
                                            value={formaPagamento.nome}
                                        >
                                            {formaPagamento.nome}
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
                                placeholder="Campo opcional"
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
                            {processing
                                ? 'Salvando...'
                                : data.tipo === 'entrada' && data.fixo_mensal
                                    ? 'Salvar entrada fixa'
                                    : data.parcela_fixa
                                        ? 'Salvar despesa fixa'
                                        : data.parcelado
                                            ? 'Salvar compra parcelada'
                                            : 'Salvar movimentação'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
