import { ConfirmDialog } from '@/components/confirm-dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';

type Resumo = {
    entradas: string | number;
    despesas: string | number;
    saldo: string | number;
};

type Movimentacao = {
    id: number;
    tipo: 'entrada' | 'despesa';
    descricao: string;
    valor: string | number;
    data: string;
    categoria: string | null;
    forma_pagamento: string | null;
    status: string;
};

type AvisoVencimentoItem = {
    id: number;
    descricao: string;
    valor: string | number;
    data: string;
    categoria: string | null;
    forma_pagamento: string | null;
    parcelado: boolean;
    parcela_atual: number | null;
    total_parcelas: number | null;
};

type AvisosVencimento = {
    vencidas: AvisoVencimentoItem[];
    vencemHoje: AvisoVencimentoItem[];
    proximosDias: AvisoVencimentoItem[];
};

type Filtros = {
    mes: string;
};

type PreferenciasNotificacao = {
    definidas: boolean;
    receber_aviso_email: boolean;
    receber_aviso_whatsapp: boolean;
    telefone_whatsapp: string | null;
};

type Props = {
    resumo?: Resumo;
    ultimasMovimentacoes?: Movimentacao[];
    avisosVencimento?: AvisosVencimento;
    filtros?: Filtros;
    preferenciasNotificacao: PreferenciasNotificacao;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

function formatarMoeda(valor: string | number) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function formatarData(data: string) {
    return new Date(`${data}T00:00:00`).toLocaleDateString('pt-BR');
}

export default function Dashboard({
    preferenciasNotificacao,
    resumo = {
        entradas: 0,
        despesas: 0,
        saldo: 0,
    },
    ultimasMovimentacoes = [],
    avisosVencimento = {
        vencidas: [],
        vencemHoje: [],
        proximosDias: [],
    },
    filtros = {
        mes: new Date().toISOString().slice(0, 7),
    },
}:
Props) {
        const [parcelaParaPagar, setParcelaParaPagar] =
            useState<AvisoVencimentoItem | null>(null);

        const [marcandoComoPago, setMarcandoComoPago] =
            useState(false);

        const {
            data: dadosNotificacao,
            setData: setDadosNotificacao,
            patch: salvarPreferencias,
            processing: salvandoPreferencias,
        } = useForm({
            receber_aviso_email:
                preferenciasNotificacao.receber_aviso_email ?? true,
        });

    const enviarPreferencias: FormEventHandler = (event) => {
        event.preventDefault();

        salvarPreferencias(route('notificacoes.update'), {
            preserveScroll: true,
        });
    };

    function alterarMes(mes: string) {
        router.get(
            '/dashboard',
            { mes },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    }

    function marcarComoPago(parcela: AvisoVencimentoItem) {
        setParcelaParaPagar(parcela);
    }

    function confirmarPagamento() {
        if (!parcelaParaPagar) {
            return;
        }

        setMarcandoComoPago(true);

        router.patch(
            `/movimentacoes/${parcelaParaPagar.id}/marcar-como-pago`,
            {},
            {
                preserveScroll: true,

                onSuccess: () => {
                    setParcelaParaPagar(null);
                },

                onFinish: () => {
                    setMarcandoComoPago(false);
                },
            },
        );
    }

    const temAvisos =
        avisosVencimento.vencidas.length > 0 ||
        avisosVencimento.vencemHoje.length > 0 ||
        avisosVencimento.proximosDias.length > 0;

    function renderizarListaAvisos(
        titulo: string,
        descricao: string,
        parcelas: AvisoVencimentoItem[],
        tipo: 'vencida' | 'hoje' | 'proxima',
    ) {
        if (parcelas.length === 0) {
            return null;
        }

        const estilos = {
            vencida:
                'border-red-200 bg-red-50 text-red-950 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100',
            hoje:
                'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100',
            proxima:
                'border-blue-200 bg-blue-50 text-blue-950 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-100',
        };

        return (
            <div className={`rounded-2xl border p-5 shadow-sm ${estilos[tipo]}`}>
                <div className="mb-4">
                    <h2 className="text-lg font-bold">{titulo}</h2>

                    <p className="mt-1 text-sm opacity-80">{descricao}</p>
                </div>

                <div className="divide-y divide-black/10 dark:divide-white/10">
                    {parcelas.map((parcela) => (
                        <div
                            key={parcela.id}
                            className="flex flex-col gap-3 py-4 md:flex-row md:items-center md:justify-between"
                        >
                            <div>
                                <p className="font-semibold">{parcela.descricao}</p>

                                <p className="mt-1 text-sm opacity-80">
                                    Vencimento: {formatarData(parcela.data)}
                                    {parcela.categoria ? ` • ${parcela.categoria}` : ''}
                                    {parcela.forma_pagamento ? ` • ${parcela.forma_pagamento}` : ''}
                                </p>
                            </div>

                            <div className="flex flex-col gap-2 md:items-end">
                                <p className="text-lg font-bold">{formatarMoeda(parcela.valor)}</p>

                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        onClick={() => marcarComoPago(parcela)}
                                        className="rounded-lg bg-green-600 px-3 py-1.5 text-center text-xs font-semibold text-white shadow-sm transition hover:bg-green-700"
                                    >
                                        Marcar como pago
                                    </button>

                                    <Link
                                        href={`/movimentacoes/${parcela.id}/edit`}
                                        className="rounded-lg bg-white px-3 py-1.5 text-center text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-100 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                                    >
                                        Ver movimentação
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Controle Financeiro" />
            {!preferenciasNotificacao.definidas && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="titulo-preferencias-notificacao"
                        className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-border bg-card p-6 shadow-2xl"
                    >
                        <div className="mb-6">
                            <p className="text-sm font-semibold text-primary">
                                Configuração inicial
                            </p>

                            <h2
                                id="titulo-preferencias-notificacao"
                                className="mt-1 text-2xl font-bold text-foreground"
                            >
                                Como deseja receber seus avisos?
                            </h2>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Escolha como o Controle Financeiro deverá avisar
                                sobre despesas próximas do vencimento. Você poderá
                                alterar essa opção depois em Configurações.
                            </p>
                        </div>

                        <form
                            onSubmit={enviarPreferencias}
                            className="space-y-5"
                        >
                            <label
                                htmlFor="modal_receber_aviso_email"
                                className="flex cursor-pointer items-start gap-3 rounded-xl border border-border p-4 transition hover:bg-muted/50"
                            >
                                <input
                                    id="modal_receber_aviso_email"
                                    type="checkbox"
                                    checked={
                                        dadosNotificacao.receber_aviso_email
                                    }
                                    onChange={(event) =>
                                        setDadosNotificacao(
                                            'receber_aviso_email',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 cursor-pointer rounded border-input"
                                />

                                <div>
                                    <p className="font-semibold text-foreground">
                                        Receber por e-mail
                                    </p>

                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Os avisos serão enviados para o e-mail da
                                        sua conta.
                                    </p>
                                </div>
                            </label>

                            <div className="flex items-start gap-3 rounded-xl border border-dashed border-border bg-muted/40 p-4 opacity-70">
                                <input
                                    id="modal_receber_aviso_whatsapp"
                                    type="checkbox"
                                    checked={false}
                                    disabled
                                    readOnly
                                    className="mt-1 h-4 w-4 cursor-not-allowed rounded border-input"
                                />

                                <div className="flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-foreground">
                                            Receber pelo WhatsApp
                                        </p>

                                        <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground">
                                            Indisponível no momento
                                        </span>
                                    </div>

                                    <p className="mt-1 text-sm text-muted-foreground">
                                        A integração com o WhatsApp está temporariamente
                                        indisponível. Os avisos continuam funcionando
                                        normalmente por e-mail.
                                    </p>
                                </div>
                            </div>



                            {!dadosNotificacao.receber_aviso_email &&
                                (
                                    <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                                        Você não receberá avisos de vencimento enquanto o envio por e-mail estiver desativado.
                                    </div>
                                )}

                            <button
                                type="submit"
                                disabled={salvandoPreferencias}
                                className="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {salvandoPreferencias
                                    ? 'Salvando...'
                                    : 'Salvar preferências'}
                            </button>
                        </form>
                    </div>
                </div>
            )}
            <div className="flex flex-col gap-6 p-4">
                <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-primary">
                                Dashboard financeiro
                            </p>

                            <h1 className="mt-1 text-2xl font-bold text-foreground">
                                Controle Financeiro
                            </h1>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Acompanhe suas entradas, despesas e saldo do mês selecionado.
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium text-foreground">
                                    Filtrar por mês
                                </label>

                                <input
                                    type="month"
                                    value={filtros.mes}
                                    onChange={(event) => alterarMes(event.target.value)}
                                    className="rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <Link
                                href="/movimentacoes"
                                className="rounded-lg bg-primary px-4 py-2 text-center text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90"
                            >
                                Ver movimentações
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Entradas do mês
                            </p>

                            <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">
                                Entrada
                            </span>
                        </div>

                        <h2 className="mt-3 text-2xl font-bold text-green-600">
                            {formatarMoeda(resumo.entradas)}
                        </h2>
                    </div>

                    <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Despesas do mês
                            </p>

                            <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">
                                Saída
                            </span>
                        </div>

                        <h2 className="mt-3 text-2xl font-bold text-red-600">
                            {formatarMoeda(resumo.despesas)}
                        </h2>
                    </div>

                    <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Saldo atual
                            </p>

                            <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                Saldo
                            </span>
                        </div>

                        <h2 className="mt-3 text-2xl font-bold text-primary">
                            {formatarMoeda(resumo.saldo)}
                        </h2>
                    </div>
                </div>

                {temAvisos && (
                    <div className="flex flex-col gap-4">
                        {renderizarListaAvisos(
                            'Parcelas vencidas',
                            'Estas despesas estão pendentes e já passaram da data de vencimento.',
                            avisosVencimento.vencidas,
                            'vencida',
                        )}

                        {renderizarListaAvisos(
                            'Parcelas vencem hoje',
                            'Estas despesas pendentes vencem hoje.',
                            avisosVencimento.vencemHoje,
                            'hoje',
                        )}

                        {renderizarListaAvisos(
                            'Parcelas próximas do vencimento',
                            'Estas despesas pendentes vencem nos próximos 7 dias.',
                            avisosVencimento.proximosDias,
                            'proxima',
                        )}
                    </div>
                )}

                <div className="flex flex-col gap-3 rounded-2xl border border-border bg-card p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-foreground">
                            Movimentações financeiras
                        </h2>

                        <p className="text-sm text-muted-foreground">
                            Cadastre entradas e despesas para acompanhar o caixa.
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Link
                            href="/movimentacoes/create?tipo=entrada"
                            className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700"
                        >
                            Nova Entrada
                        </Link>

                        <Link
                            href="/movimentacoes/create?tipo=despesa"
                            className="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-90"
                        >
                            Nova Despesa
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                    <div className="border-b border-border p-5">
                        <h2 className="text-lg font-bold text-foreground">
                            Últimas movimentações
                        </h2>
                    </div>

                    {ultimasMovimentacoes.length === 0 ? (
                        <div className="p-8 text-center">
                            <p className="text-sm text-muted-foreground">
                                Nenhuma movimentação cadastrada ainda.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted text-muted-foreground">
                                    <tr>
                                        <th className="px-5 py-3 font-semibold">Data</th>
                                        <th className="px-5 py-3 font-semibold">Tipo</th>
                                        <th className="px-5 py-3 font-semibold">Descrição</th>
                                        <th className="px-5 py-3 font-semibold">Categoria</th>
                                        <th className="px-5 py-3 text-right font-semibold">
                                            Valor
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {ultimasMovimentacoes.map((movimentacao) => (
                                        <tr
                                            key={movimentacao.id}
                                            className="transition hover:bg-muted/50"
                                        >
                                            <td className="px-5 py-4 text-muted-foreground">
                                                {formatarData(movimentacao.data)}
                                            </td>

                                            <td className="px-5 py-4">
                                                <span
                                                    className={
                                                        movimentacao.tipo === 'entrada'
                                                            ? 'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300'
                                                            : 'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300'
                                                    }
                                                >
                                                    {movimentacao.tipo === 'entrada'
                                                        ? 'Entrada'
                                                        : 'Despesa'}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4 font-semibold text-foreground">
                                                {movimentacao.descricao}
                                            </td>

                                            <td className="px-5 py-4 text-muted-foreground">
                                                {movimentacao.categoria || '-'}
                                            </td>

                                            <td
                                                className={
                                                    movimentacao.tipo === 'entrada'
                                                        ? 'px-5 py-4 text-right font-bold text-green-600'
                                                        : 'px-5 py-4 text-right font-bold text-red-600'
                                                }
                                            >
                                                {formatarMoeda(movimentacao.valor)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
            {parcelaParaPagar && (
                <ConfirmDialog
                    open
                    title="Marcar parcela como paga"
                    description={`Confirma o pagamento de "${parcelaParaPagar.descricao}", no valor de ${formatarMoeda(parcelaParaPagar.valor)}?`}
                    confirmLabel="Marcar como paga"
                    variant="info"
                    processing={marcandoComoPago}
                    onConfirm={confirmarPagamento}
                    onClose={() => setParcelaParaPagar(null)}
                />
            )}
        </AppLayout>
    );
}
