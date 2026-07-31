import { ConfirmDialog } from '@/components/confirm-dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

type Resumo = {
    entradas: string | number;
    despesas: string | number;
    saldo: string | number;
};

type PeriodoGrafico = 'semana' | 'mes' | 'ano';

type TipoGrafico = 'geral' | 'despesas' | 'entradas';

type PontoGrafico = {
    rotulo: string;
    data: string;
    valor: string | number;
    entradas: string | number;
    despesas: string | number;
};

type GraficoGastos = {
    periodo: PeriodoGrafico;
    titulo: string;
    unidade_media: 'dia' | 'mês';

    total: string | number;
    media: string | number;
    total_anterior: string | number;
    comparacao_percentual: number | null;

    totais: {
        entradas: string | number;
        despesas: string | number;
        saldo: string | number;
    };

    medias: {
        entradas: string | number;
        despesas: string | number;
    };

    anterior: {
        entradas: string | number;
        despesas: string | number;
        saldo: string | number;
    };

    comparacoes: {
        entradas: number | null;
        despesas: number | null;
    };

    pontos: PontoGrafico[];
};

type TooltipGraficoItem = {
    dataKey?: string | number;
    name?: string;
    value?: string | number;
    color?: string;
    payload?: PontoGrafico;
};

type TooltipGraficoProps = {
    active?: boolean;
    label?: string | number;
    payload?: TooltipGraficoItem[];
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

    parcelado: boolean;
    parcela_fixa: boolean;
    despesa_fixa_id: number | null;

    fixo_mensal: boolean;
    entrada_fixa_id: number | null;

    parcela_atual: number | null;
    total_parcelas: number | null;
};

type AvisoVencimentoItem = {
    id: number;
    descricao: string;
    valor: string | number;
    data: string;
    categoria: string | null;
    forma_pagamento: string | null;

    parcelado: boolean;
    parcela_fixa: boolean;
    despesa_fixa_id: number | null;
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
    periodo_grafico: PeriodoGrafico;
};

type PreferenciasNotificacao = {
    definidas: boolean;
    receber_aviso_email: boolean;
    receber_aviso_whatsapp: boolean;
    telefone_whatsapp: string | null;
};

type Props = {
    resumo?: Resumo;
    graficoGastos?: GraficoGastos;
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

function obterMesAtual() {
    const hoje = new Date();

    return `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}`;
}

function formatarValorCompacto(valor: string | number) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        notation: 'compact',
        maximumFractionDigits: 1,
    });
}

function formatarReferenciaGrafico(data: string) {
    if (/^\d{4}-\d{2}$/.test(data)) {
        const [ano, mes] = data.split('-').map(Number);

        return new Date(ano, mes - 1, 1).toLocaleDateString('pt-BR', {
            month: 'long',
            year: 'numeric',
        });
    }

    return formatarData(data);
}

function textoComparacao(percentual: number | null) {
    if (percentual === null) {
        return 'Sem base no período anterior';
    }

    if (percentual === 0) {
        return 'Mesmo total do período anterior';
    }

    const valor = Math.abs(percentual).toLocaleString('pt-BR', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    });

    return percentual > 0 ? `${valor}% a mais que o período anterior` : `${valor}% a menos que o período anterior`;
}

function classeComparacao(tipo: 'entradas' | 'despesas', percentual: number | null) {
    if (percentual === null || percentual === 0) {
        return 'mt-1 text-xs text-muted-foreground';
    }

    const resultadoFavoravel = tipo === 'entradas' ? percentual > 0 : percentual < 0;

    return resultadoFavoravel ? 'mt-1 text-xs font-medium text-green-600' : 'mt-1 text-xs font-medium text-red-600';
}

function TooltipGrafico({ active, payload, label }: TooltipGraficoProps) {
    if (!active || !payload?.length) {
        return null;
    }

    const ponto = payload[0]?.payload;

    return (
        <div className="border-border bg-card rounded-xl border p-3 shadow-lg">
            <p className="text-muted-foreground text-xs font-medium">{ponto?.data ? formatarReferenciaGrafico(ponto.data) : label}</p>

            <div className="mt-2 space-y-1">
                {payload.map((item) => {
                    const nome = item.dataKey === 'entradas' ? 'Entradas' : 'Despesas';

                    return (
                        <p
                            key={String(item.dataKey)}
                            className="text-sm font-bold"
                            style={{
                                color: item.color,
                            }}
                        >
                            {nome}: {formatarMoeda(item.value ?? 0)}
                        </p>
                    );
                })}
            </div>
        </div>
    );
}

const periodosGrafico: Array<{
    valor: PeriodoGrafico;
    titulo: string;
}> = [
    {
        valor: 'semana',
        titulo: 'Semana',
    },
    {
        valor: 'mes',
        titulo: 'Mês',
    },
    {
        valor: 'ano',
        titulo: 'Ano',
    },
];

const tiposGrafico: Array<{
    valor: TipoGrafico;
    titulo: string;
}> = [
    {
        valor: 'geral',
        titulo: 'Geral',
    },
    {
        valor: 'despesas',
        titulo: 'Despesas',
    },
    {
        valor: 'entradas',
        titulo: 'Entradas',
    },
];
function identificarLancamento(movimentacao: {
    parcelado: boolean;
    parcela_fixa: boolean;
    despesa_fixa_id: number | null;

    fixo_mensal?: boolean;
    entrada_fixa_id?: number | null;

    parcela_atual: number | null;
    total_parcelas: number | null;
}): string | null {
    if (movimentacao.fixo_mensal && movimentacao.entrada_fixa_id) {
        return 'Entrada fixa';
    }

    if (movimentacao.parcela_fixa && movimentacao.despesa_fixa_id) {
        return 'Parcela fixa';
    }

    if (movimentacao.parcelado && movimentacao.parcela_atual && movimentacao.total_parcelas) {
        return `Parcela ${movimentacao.parcela_atual}/${movimentacao.total_parcelas}`;
    }

    return null;
}
export default function Dashboard({
    preferenciasNotificacao,
    resumo = {
        entradas: 0,
        despesas: 0,
        saldo: 0,
    },

    graficoGastos = {
        periodo: 'mes',
        titulo: '',
        unidade_media: 'dia',

        total: 0,
        media: 0,
        total_anterior: 0,
        comparacao_percentual: null,

        totais: {
            entradas: 0,
            despesas: 0,
            saldo: 0,
        },

        medias: {
            entradas: 0,
            despesas: 0,
        },

        anterior: {
            entradas: 0,
            despesas: 0,
            saldo: 0,
        },

        comparacoes: {
            entradas: null,
            despesas: null,
        },

        pontos: [],
    },
    ultimasMovimentacoes = [],
    avisosVencimento = {
        vencidas: [],
        vencemHoje: [],
        proximosDias: [],
    },
    filtros = {
        mes: obterMesAtual(),
        periodo_grafico: 'mes',
    },
}: Props) {
    const [parcelaParaPagar, setParcelaParaPagar] = useState<AvisoVencimentoItem | null>(null);

    const [marcandoComoPago, setMarcandoComoPago] = useState(false);

    const [tipoGrafico, setTipoGrafico] = useState<TipoGrafico>('geral');

    const {
        data: dadosNotificacao,
        setData: setDadosNotificacao,
        patch: salvarPreferencias,
        processing: salvandoPreferencias,
    } = useForm({
        receber_aviso_email: preferenciasNotificacao.receber_aviso_email ?? true,
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
            {
                mes,
                periodo_grafico: filtros.periodo_grafico,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function alterarPeriodoGrafico(periodo: PeriodoGrafico) {
        router.get(
            '/dashboard',
            {
                mes: filtros.mes,
                periodo_grafico: periodo,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
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

    const temAvisos = avisosVencimento.vencidas.length > 0 || avisosVencimento.vencemHoje.length > 0 || avisosVencimento.proximosDias.length > 0;

    const tipoDetalhado: 'entradas' | 'despesas' = tipoGrafico === 'entradas' ? 'entradas' : 'despesas';

    const totalDetalhado = graficoGastos.totais[tipoDetalhado];

    const mediaDetalhada = graficoGastos.medias[tipoDetalhado];

    const totalAnteriorDetalhado = graficoGastos.anterior[tipoDetalhado];

    const comparacaoDetalhada = graficoGastos.comparacoes[tipoDetalhado];

    function renderizarListaAvisos(titulo: string, descricao: string, parcelas: AvisoVencimentoItem[], tipo: 'vencida' | 'hoje' | 'proxima') {
        if (parcelas.length === 0) {
            return null;
        }

        const estilos = {
            vencida: 'border-red-200 bg-red-50 text-red-950 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-100',
            hoje: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100',
            proxima: 'border-blue-200 bg-blue-50 text-blue-950 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-100',
        };

        return (
            <div className={`rounded-2xl border p-5 shadow-sm ${estilos[tipo]}`}>
                <div className="mb-4">
                    <h2 className="text-lg font-bold">{titulo}</h2>

                    <p className="mt-1 text-sm opacity-80">{descricao}</p>
                </div>

                <div className="divide-y divide-black/10 dark:divide-white/10">
                    {parcelas.map((parcela) => (
                        <div key={parcela.id} className="flex flex-col gap-3 py-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p className="font-semibold">{parcela.descricao}</p>

                                {identificarLancamento(parcela) && (
                                    <p className="mt-1 text-xs font-semibold opacity-80">{identificarLancamento(parcela)}</p>
                                )}

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
                        className="border-border bg-card max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border p-6 shadow-2xl"
                    >
                        <div className="mb-6">
                            <p className="text-primary text-sm font-semibold">Configuração inicial</p>

                            <h2 id="titulo-preferencias-notificacao" className="text-foreground mt-1 text-2xl font-bold">
                                Como deseja receber seus avisos?
                            </h2>

                            <p className="text-muted-foreground mt-2 text-sm">
                                Escolha como o Controle Financeiro deverá avisar sobre despesas próximas do vencimento. Você poderá alterar essa opção
                                depois em Configurações.
                            </p>
                        </div>

                        <form onSubmit={enviarPreferencias} className="space-y-5">
                            <label
                                htmlFor="modal_receber_aviso_email"
                                className="border-border hover:bg-muted/50 flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                            >
                                <input
                                    id="modal_receber_aviso_email"
                                    type="checkbox"
                                    checked={dadosNotificacao.receber_aviso_email}
                                    onChange={(event) => setDadosNotificacao('receber_aviso_email', event.target.checked)}
                                    className="border-input mt-1 h-4 w-4 cursor-pointer rounded"
                                />

                                <div>
                                    <p className="text-foreground font-semibold">Receber por e-mail</p>

                                    <p className="text-muted-foreground mt-1 text-sm">Os avisos serão enviados para o e-mail da sua conta.</p>
                                </div>
                            </label>

                            <div className="border-border bg-muted/40 flex items-start gap-3 rounded-xl border border-dashed p-4 opacity-70">
                                <input
                                    id="modal_receber_aviso_whatsapp"
                                    type="checkbox"
                                    checked={false}
                                    disabled
                                    readOnly
                                    className="border-input mt-1 h-4 w-4 cursor-not-allowed rounded"
                                />

                                <div className="flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-foreground font-semibold">Receber pelo WhatsApp</p>

                                        <span className="bg-muted text-muted-foreground rounded-full px-2.5 py-1 text-xs font-semibold">
                                            Indisponível no momento
                                        </span>
                                    </div>

                                    <p className="text-muted-foreground mt-1 text-sm">
                                        A integração com o WhatsApp está temporariamente indisponível. Os avisos continuam funcionando normalmente por
                                        e-mail.
                                    </p>
                                </div>
                            </div>

                            {!dadosNotificacao.receber_aviso_email && (
                                <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                                    Você não receberá avisos de vencimento enquanto o envio por e-mail estiver desativado.
                                </div>
                            )}

                            <button
                                type="submit"
                                disabled={salvandoPreferencias}
                                className="bg-primary text-primary-foreground w-full rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {salvandoPreferencias ? 'Salvando...' : 'Salvar preferências'}
                            </button>
                        </form>
                    </div>
                </div>
            )}
            <div className="flex flex-col gap-6 p-4">
                <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-primary text-sm font-semibold">Dashboard financeiro</p>

                            <h1 className="text-foreground mt-1 text-2xl font-bold">Controle Financeiro</h1>

                            <p className="text-muted-foreground mt-1 text-sm">Acompanhe suas entradas, despesas e saldo do mês selecionado.</p>
                        </div>

                        <div className="flex flex-col gap-3 md:flex-row md:items-end">
                            <div className="flex flex-col gap-1">
                                <label className="text-foreground text-sm font-medium">Filtrar por mês</label>

                                <input
                                    type="month"
                                    value={filtros.mes}
                                    onChange={(event) => alterarMes(event.target.value)}
                                    className="border-input bg-background text-foreground focus:border-primary focus:ring-primary/20 rounded-lg border px-3 py-2 text-sm transition outline-none focus:ring-2"
                                />
                            </div>

                            <Link
                                href="/movimentacoes"
                                className="bg-primary text-primary-foreground rounded-lg px-4 py-2 text-center text-sm font-semibold shadow-sm transition hover:opacity-90"
                            >
                                Ver movimentações
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-muted-foreground text-sm font-medium">Entradas do mês</p>

                            <span className="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">
                                Entrada
                            </span>
                        </div>

                        <h2 className="mt-3 text-2xl font-bold text-green-600">{formatarMoeda(resumo.entradas)}</h2>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-muted-foreground text-sm font-medium">Despesas do mês</p>

                            <span className="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">
                                Saída
                            </span>
                        </div>

                        <h2 className="mt-3 text-2xl font-bold text-red-600">{formatarMoeda(resumo.despesas)}</h2>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-muted-foreground text-sm font-medium">Saldo atual</p>

                            <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                Saldo
                            </span>
                        </div>

                        <h2 className="text-primary mt-3 text-2xl font-bold">{formatarMoeda(resumo.saldo)}</h2>
                    </div>
                </div>

                {temAvisos && (
                    <div className="flex flex-col gap-4">
                        {renderizarListaAvisos(
                            'Despesas vencidas',
                            'Estas despesas estão pendentes e já passaram da data de vencimento.',
                            avisosVencimento.vencidas,
                            'vencida',
                        )}

                        {renderizarListaAvisos('Despesas vencem hoje', 'Estas despesas pendentes vencem hoje.', avisosVencimento.vencemHoje, 'hoje')}

                        {renderizarListaAvisos(
                            'Despesas próximas do vencimento',
                            'Estas despesas pendentes vencem nos próximos 7 dias.',
                            avisosVencimento.proximosDias,
                            'proxima',
                        )}
                    </div>
                )}

                <div className="border-border bg-card flex flex-col gap-3 rounded-2xl border p-5 shadow-sm md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-foreground text-lg font-bold">Movimentações financeiras</h2>

                        <p className="text-muted-foreground text-sm">Cadastre entradas e despesas para acompanhar o caixa.</p>
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
                            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                        >
                            Nova Despesa
                        </Link>
                    </div>
                </div>

                <div className="border-border bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-border border-b p-5">
                        <h2 className="text-foreground text-lg font-bold">Últimas movimentações</h2>
                    </div>

                    {ultimasMovimentacoes.length === 0 ? (
                        <div className="p-8 text-center">
                            <p className="text-muted-foreground text-sm">Nenhuma movimentação cadastrada ainda.</p>
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
                                        <th className="px-5 py-3 text-right font-semibold">Valor</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-border divide-y">
                                    {ultimasMovimentacoes.map((movimentacao) => (
                                        <tr key={movimentacao.id} className="hover:bg-muted/50 transition">
                                            <td className="text-muted-foreground px-5 py-4">{formatarData(movimentacao.data)}</td>

                                            <td className="px-5 py-4">
                                                <span
                                                    className={
                                                        movimentacao.tipo === 'entrada'
                                                            ? 'rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300'
                                                            : 'rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300'
                                                    }
                                                >
                                                    {movimentacao.tipo === 'entrada' ? 'Entrada' : 'Despesa'}
                                                </span>
                                            </td>

                                            <td className="px-5 py-4">
                                                <p className="text-foreground font-semibold">{movimentacao.descricao}</p>

                                                {identificarLancamento(movimentacao) && (
                                                    <p
                                                        className={
                                                            movimentacao.fixo_mensal && movimentacao.entrada_fixa_id
                                                                ? 'mt-1 text-xs font-medium text-green-600 dark:text-green-400'
                                                                : 'mt-1 text-xs font-medium text-blue-600 dark:text-blue-400'
                                                        }
                                                    >
                                                        {identificarLancamento(movimentacao)}
                                                    </p>
                                                )}
                                            </td>

                                            <td className="text-muted-foreground px-5 py-4">{movimentacao.categoria || '-'}</td>

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

                <div className="border-border bg-card overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-border flex flex-col gap-5 border-b p-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-primary text-sm font-semibold">Análise financeira</p>

                            <h2 className="text-foreground mt-1 text-xl font-bold">Entradas e despesas por período</h2>

                            <p className="text-muted-foreground mt-1 text-sm">Compare suas entradas, despesas e o saldo por semana, mês ou ano.</p>
                        </div>

                        <div className="flex w-full flex-col gap-3 lg:w-auto">
                            <div className="bg-muted inline-flex w-full rounded-xl p-1 lg:w-auto">
                                {tiposGrafico.map((tipo) => (
                                    <button
                                        key={tipo.valor}
                                        type="button"
                                        onClick={() => setTipoGrafico(tipo.valor)}
                                        className={
                                            tipoGrafico === tipo.valor
                                                ? 'bg-background text-foreground flex-1 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition lg:flex-none'
                                                : 'text-muted-foreground hover:text-foreground flex-1 rounded-lg px-4 py-2 text-sm font-medium transition lg:flex-none'
                                        }
                                    >
                                        {tipo.titulo}
                                    </button>
                                ))}
                            </div>

                            <div className="bg-muted inline-flex w-full rounded-xl p-1 lg:w-auto">
                                {periodosGrafico.map((periodo) => (
                                    <button
                                        key={periodo.valor}
                                        type="button"
                                        onClick={() => alterarPeriodoGrafico(periodo.valor)}
                                        className={
                                            graficoGastos.periodo === periodo.valor
                                                ? 'bg-background text-foreground flex-1 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition lg:flex-none'
                                                : 'text-muted-foreground hover:text-foreground flex-1 rounded-lg px-4 py-2 text-sm font-medium transition lg:flex-none'
                                        }
                                    >
                                        {periodo.titulo}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {tipoGrafico === 'geral' ? (
                        <div className="grid gap-4 p-5 md:grid-cols-3">
                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">Total de entradas</p>

                                <p className="mt-2 text-2xl font-bold text-green-600">{formatarMoeda(graficoGastos.totais.entradas)}</p>

                                <p className="text-muted-foreground mt-1 text-xs">{graficoGastos.titulo}</p>
                            </div>

                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">Total de despesas</p>

                                <p className="mt-2 text-2xl font-bold text-red-600">{formatarMoeda(graficoGastos.totais.despesas)}</p>

                                <p className="text-muted-foreground mt-1 text-xs">{graficoGastos.titulo}</p>
                            </div>

                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">Saldo do período</p>

                                <p
                                    className={
                                        Number(graficoGastos.totais.saldo) >= 0
                                            ? 'mt-2 text-2xl font-bold text-green-600'
                                            : 'mt-2 text-2xl font-bold text-red-600'
                                    }
                                >
                                    {formatarMoeda(graficoGastos.totais.saldo)}
                                </p>

                                <p className="text-muted-foreground mt-1 text-xs">Entradas menos despesas</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 p-5 md:grid-cols-3">
                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">
                                    {tipoDetalhado === 'entradas' ? 'Total de entradas' : 'Total de despesas'}
                                </p>

                                <p
                                    className={
                                        tipoDetalhado === 'entradas'
                                            ? 'mt-2 text-2xl font-bold text-green-600'
                                            : 'mt-2 text-2xl font-bold text-red-600'
                                    }
                                >
                                    {formatarMoeda(totalDetalhado)}
                                </p>

                                <p className="text-muted-foreground mt-1 text-xs">{graficoGastos.titulo}</p>
                            </div>

                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">Média por {graficoGastos.unidade_media}</p>

                                <p className="text-foreground mt-2 text-2xl font-bold">{formatarMoeda(mediaDetalhada)}</p>

                                <p className="text-muted-foreground mt-1 text-xs">Considerando o período decorrido</p>
                            </div>

                            <div className="border-border bg-background rounded-xl border p-4">
                                <p className="text-muted-foreground text-sm font-medium">Período anterior</p>

                                <p className="text-foreground mt-2 text-2xl font-bold">{formatarMoeda(totalAnteriorDetalhado)}</p>

                                <p className={classeComparacao(tipoDetalhado, comparacaoDetalhada)}>{textoComparacao(comparacaoDetalhada)}</p>
                            </div>
                        </div>
                    )}

                    <div className="h-80 px-2 pb-5 sm:px-5">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart
                                data={graficoGastos.pontos}
                                margin={{
                                    top: 10,
                                    right: 12,
                                    left: -10,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />

                                <XAxis dataKey="rotulo" axisLine={false} tickLine={false} minTickGap={18} fontSize={12} />

                                <YAxis axisLine={false} tickLine={false} width={70} fontSize={12} tickFormatter={formatarValorCompacto} />

                                <Tooltip content={<TooltipGrafico />} />

                                {tipoGrafico === 'geral' && <Legend verticalAlign="top" height={36} />}

                                {tipoGrafico !== 'despesas' && (
                                    <Line
                                        type="monotone"
                                        dataKey="entradas"
                                        name="Entradas"
                                        stroke="#16a34a"
                                        strokeWidth={3}
                                        dot={{
                                            r: 3,
                                        }}
                                        activeDot={{
                                            r: 6,
                                        }}
                                    />
                                )}

                                {tipoGrafico !== 'entradas' && (
                                    <Line
                                        type="monotone"
                                        dataKey="despesas"
                                        name="Despesas"
                                        stroke="#dc2626"
                                        strokeWidth={3}
                                        dot={{
                                            r: 3,
                                        }}
                                        activeDot={{
                                            r: 6,
                                        }}
                                    />
                                )}
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
            {parcelaParaPagar && (
                <ConfirmDialog
                    open
                    title="Marcar como pago"
                    description={`Confirma o pagamento de "${parcelaParaPagar.descricao}", no valor de ${formatarMoeda(parcelaParaPagar.valor)}?`}
                    confirmLabel="Marcar como pago"
                    variant="info"
                    processing={marcandoComoPago}
                    onConfirm={confirmarPagamento}
                    onClose={() => setParcelaParaPagar(null)}
                />
            )}
        </AppLayout>
    );
}
