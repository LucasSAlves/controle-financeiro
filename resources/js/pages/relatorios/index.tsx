import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

type TipoFiltro = 'geral' | 'entrada' | 'despesa';

type StatusFiltro = 'todos' | 'concluidos' | 'pendentes';

type PeriodoFiltro = 'semana' | 'mes' | 'ano' | 'personalizado';

type Resumo = {
    entradas: string | number;
    despesas: string | number;
    saldo: string | number;
    quantidade: number;
    concluidas: number;
    pendentes: number;
    valor_pendente: string | number;
};

type PontoEvolucao = {
    rotulo: string;
    data: string;
    entradas: string | number;
    despesas: string | number;
};

type GraficoEvolucao = {
    granularidade: 'dia' | 'mês' | 'ano';
    pontos: PontoEvolucao[];
};

type PontoCategoria = {
    categoria: string;
    entradas: string | number;
    despesas: string | number;
    quantidade: number;
};

type Graficos = {
    evolucao: GraficoEvolucao;
    porCategoria: PontoCategoria[];
};

type Movimentacao = {
    id: number;
    tipo: 'entrada' | 'despesa';
    descricao: string;
    valor: string | number;
    data: string;
    data_pagamento: string | null;
    categoria: string | null;
    forma_pagamento: string | null;
    status: string;

    parcelado: boolean;
    parcela_fixa: boolean;
    fixo_mensal: boolean;

    parcela_atual: number | null;
    total_parcelas: number | null;

    despesa_fixa_id: number | null;
    entrada_fixa_id: number | null;
};

type LinkPaginacao = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginacao<T> = {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: LinkPaginacao[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

type Filtros = {
    periodo: PeriodoFiltro;
    referencia: string;
    data_inicio: string;
    data_fim: string;
    tipo: TipoFiltro;
    status: StatusFiltro;
    categoria: string;
    forma_pagamento: string;
};

type PeriodoSelecionado = {
    inicio: string;
    fim: string;
    titulo: string;
};

type Opcoes = {
    categorias: string[];
    formasPagamento: string[];
};

type Props = {
    resumo: Resumo;
    graficos: Graficos;
    movimentacoes: Paginacao<Movimentacao>;
    filtros: Filtros;
    periodoSelecionado: PeriodoSelecionado;
    opcoes: Opcoes;
};

type TooltipItem = {
    dataKey?: string | number;
    name?: string;
    value?: string | number;
    color?: string;
    payload?: PontoEvolucao | PontoCategoria;
};

type TooltipProps = {
    active?: boolean;
    label?: string | number;
    payload?: TooltipItem[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Relatórios',
        href: '/relatorios',
    },
];

const classeCampoFiltro =
    'border-input bg-background text-foreground focus:border-primary focus:ring-primary/20 box-border block h-10 w-full min-w-0 max-w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2';

function obterDataAtual(): string {
    const hoje = new Date();

    return [hoje.getFullYear(), String(hoje.getMonth() + 1).padStart(2, '0'), String(hoje.getDate()).padStart(2, '0')].join('-');
}

function formatarMoeda(valor: string | number) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function formatarValorCompacto(valor: string | number) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        notation: 'compact',
        maximumFractionDigits: 1,
    });
}

function formatarData(data: string) {
    return new Date(`${data}T00:00:00`).toLocaleDateString('pt-BR');
}

function formatarStatus(movimentacao: Movimentacao) {
    if (movimentacao.status === 'pago' || movimentacao.data_pagamento) {
        return 'Pago';
    }

    if (movimentacao.status === 'recebido') {
        return 'Recebido';
    }

    return 'Pendente';
}

function classeStatus(movimentacao: Movimentacao) {
    if (movimentacao.status === 'pago' || movimentacao.data_pagamento) {
        return 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300';
    }

    if (movimentacao.status === 'recebido') {
        return 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300';
    }

    return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
}

function identificarLancamento(movimentacao: Movimentacao): string | null {
    if (movimentacao.fixo_mensal && movimentacao.entrada_fixa_id) {
        return 'Entrada fixa';
    }

    if (movimentacao.parcela_fixa && movimentacao.despesa_fixa_id) {
        return 'Despesa fixa';
    }

    if (movimentacao.parcelado && movimentacao.parcela_atual && movimentacao.total_parcelas) {
        return `Parcela ${movimentacao.parcela_atual}/${movimentacao.total_parcelas}`;
    }

    return null;
}

function formatarLabelPaginacao(label: string) {
    if (label.includes('Previous') || label.includes('Anterior')) {
        return 'Anterior';
    }

    if (label.includes('Next') || label.includes('Próxima')) {
        return 'Próxima';
    }

    return label;
}

function TooltipFinanceiro({ active, payload, label }: TooltipProps) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="border-border bg-card rounded-xl border p-3 shadow-lg">
            <p className="text-muted-foreground text-xs font-semibold">{label}</p>

            <div className="mt-2 space-y-1">
                {payload.map((item) => (
                    <p
                        key={String(item.dataKey)}
                        className="text-sm font-bold"
                        style={{
                            color: item.color,
                        }}
                    >
                        {item.name}: {formatarMoeda(item.value ?? 0)}
                    </p>
                ))}
            </div>
        </div>
    );
}

export default function RelatoriosIndex({ resumo, graficos, movimentacoes, filtros, periodoSelecionado, opcoes }: Props) {
    const { data, setData, get, processing, errors, setError, clearErrors, } = useForm({
        periodo: filtros.periodo,
        referencia: filtros.referencia || obterDataAtual(),
        data_inicio: filtros.data_inicio,
        data_fim: filtros.data_fim,
        tipo: filtros.tipo,
        status: filtros.status,
        categoria: filtros.categoria,
        forma_pagamento: filtros.forma_pagamento,
    });

    const [toastErro, setToastErro] = useState<string | null>(null);

    function exibirToastErro(mensagem: string) {
        setToastErro(mensagem);

        window.setTimeout(() => {
            setToastErro(null);
        }, 4000);
    }

    function registrarErroFormulario(
        campo: 'data_inicio' | 'data_fim',
        mensagem: string,
    ) {
        setError(campo, mensagem);
        exibirToastErro(mensagem);
    }

    const enviarFiltros: FormEventHandler = (event) => {
        event.preventDefault();

        clearErrors();
        setToastErro(null);

        if (data.periodo === 'personalizado') {
            if (!data.data_inicio) {
                registrarErroFormulario(
                    'data_inicio',
                    'Informe a data inicial.',
                );

                return;
            }

            if (!data.data_fim) {
                registrarErroFormulario(
                    'data_fim',
                    'Informe a data final.',
                );

                return;
            }

            if (data.data_fim < data.data_inicio) {
                registrarErroFormulario(
                    'data_fim',
                    'A data final deve ser igual ou posterior à data inicial.',
                );

                return;
            }

            const dataInicial = new Date(
                `${data.data_inicio}T00:00:00`,
            );

            const dataFinal = new Date(
                `${data.data_fim}T00:00:00`,
            );

            const limiteMaximo = new Date(dataInicial);

            limiteMaximo.setFullYear(
                limiteMaximo.getFullYear() + 5,
            );

            if (dataFinal > limiteMaximo) {
                registrarErroFormulario(
                    'data_fim',
                    'O período máximo permitido é de cinco anos.',
                );

                return;
            }
        }

        get('/relatorios', {
            preserveScroll: true,
            replace: true,

            onError: (errosRecebidos) => {
                const primeiraMensagem = Object.values(
                    errosRecebidos,
                ).find(
                    (erro): erro is string =>
                        typeof erro === 'string'
                        && erro.trim() !== '',
                );

                exibirToastErro(
                    primeiraMensagem
                    ?? 'Verifique os campos informados e tente novamente.',
                );
            },
        });
    };

    function limparFiltros() {
        router.get(
            '/relatorios',
            {},
            {
                preserveScroll: true,
                replace: true,
            },
        );
    }

    const mostrarEntradas = data.tipo !== 'despesa';
    const mostrarDespesas = data.tipo !== 'entrada';

    const categoriasGrafico = graficos.porCategoria.slice(0, 10);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Relatórios" />

            {toastErro && (
            <div
                role="alert"
                className="fixed top-4 right-4 left-4 z-[100] rounded-xl border border-red-200 bg-red-50 p-4 shadow-xl sm:left-auto sm:w-full sm:max-w-md dark:border-red-900 dark:bg-red-950"
            >
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="font-bold text-red-700 dark:text-red-300">
                            Não foi possível gerar o relatório
                        </p>

                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                            {toastErro}
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={() => setToastErro(null)}
                        className="rounded-lg px-2 py-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900"
                        aria-label="Fechar aviso"
                    >
                        ×
                    </button>
                </div>
            </div>
        )}

            <div className="box-border flex w-full max-w-[100dvw] min-w-0 flex-col gap-4 overflow-x-hidden p-3 sm:max-w-full sm:gap-6 sm:p-4">
                <div className="border-border bg-card w-full min-w-0 max-w-full overflow-hidden rounded-2xl border p-4 shadow-sm sm:p-5">
                    <div>
                        <p className="text-primary text-sm font-semibold">Análise financeira</p>

                        <h1 className="text-foreground mt-1 text-2xl font-bold">Relatórios</h1>

                        <p className="text-muted-foreground mt-1 max-w-full break-words text-sm">
                            Analise entradas, despesas, saldo, categorias e movimentações em qualquer período.
                        </p>
                    </div>
                </div>

                <form
                    onSubmit={enviarFiltros}
                    className="border-border bg-card w-full min-w-0 max-w-full rounded-2xl border p-4 shadow-sm sm:p-5"
                >
                    <div className="mb-5">
                        <h2 className="text-foreground text-lg font-bold">Filtros do relatório</h2>

                        <p className="text-muted-foreground mt-1 text-sm">Selecione as informações que deseja analisar.</p>
                    </div>

                    <div className="grid w-full min-w-0 max-w-full grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="flex min-w-0 flex-col gap-1">
                            <label htmlFor="periodo" className="text-foreground text-sm font-medium">
                                Período
                            </label>

                            <select
                                id="periodo"
                                value={data.periodo}
                                onChange={(event) => setData('periodo', event.target.value as PeriodoFiltro)}
                                className={classeCampoFiltro}
                            >
                                <option value="semana">Semana</option>
                                <option value="mes">Mês</option>
                                <option value="ano">Ano</option>
                                <option value="personalizado">Personalizado</option>
                            </select>
                        </div>

                        {data.periodo !== 'personalizado' && (
                            <div className="flex min-w-0 flex-col gap-1">
                                <label htmlFor="referencia" className="text-foreground text-sm font-medium">
                                    Data de referência
                                </label>

                                <input
                                    id="referencia"
                                    type="date"
                                    value={data.referencia}
                                    onChange={(event) => setData('referencia', event.target.value)}
                                    className={classeCampoFiltro}
                                />
                            </div>
                        )}

                        {data.periodo === 'personalizado' && (
                            <>
                                <div className="flex min-w-0 flex-col gap-1">
                                    <label htmlFor="data_inicio" className="text-foreground text-sm font-medium">
                                        Data inicial
                                    </label>

                                    <input
                                        id="data_inicio"
                                        type="date"
                                        value={data.data_inicio}
                                        onChange={(event) => setData('data_inicio', event.target.value)}
                                        className={classeCampoFiltro}
                                    />

                                    {errors.data_inicio && <p className="text-xs text-red-600">{errors.data_inicio}</p>}
                                </div>

                                <div className="flex min-w-0 flex-col gap-1">
                                    <label htmlFor="data_fim" className="text-foreground text-sm font-medium">
                                        Data final
                                    </label>

                                    <input
                                        id="data_fim"
                                        type="date"
                                        value={data.data_fim}
                                        onChange={(event) => setData('data_fim', event.target.value)}
                                        className={classeCampoFiltro}
                                    />

                                    {errors.data_fim && <p className="text-xs text-red-600">{errors.data_fim}</p>}
                                </div>
                            </>
                        )}

                        <div className="flex min-w-0 flex-col gap-1">
                            <label htmlFor="tipo" className="text-foreground text-sm font-medium">
                                Tipo
                            </label>

                            <select
                                id="tipo"
                                value={data.tipo}
                                onChange={(event) => setData('tipo', event.target.value as TipoFiltro)}
                                className={classeCampoFiltro}
                            >
                                <option value="geral">Geral</option>
                                <option value="entrada">Entradas</option>
                                <option value="despesa">Despesas</option>
                            </select>
                        </div>

                        <div className="flex min-w-0 flex-col gap-1">
                            <label htmlFor="status" className="text-foreground text-sm font-medium">
                                Status
                            </label>

                            <select
                                id="status"
                                value={data.status}
                                onChange={(event) => setData('status', event.target.value as StatusFiltro)}
                                className={classeCampoFiltro}
                            >
                                <option value="todos">Todos</option>
                                <option value="concluidos">Concluídos</option>
                                <option value="pendentes">Pendentes</option>
                            </select>
                        </div>

                        <div className="flex min-w-0 flex-col gap-1">
                            <label htmlFor="categoria" className="text-foreground text-sm font-medium">
                                Categoria
                            </label>

                            <select
                                id="categoria"
                                value={data.categoria}
                                onChange={(event) => setData('categoria', event.target.value)}
                                className={classeCampoFiltro}
                            >
                                <option value="">Todas</option>

                                {opcoes.categorias.map((categoria) => (
                                    <option key={categoria} value={categoria}>
                                        {categoria}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex min-w-0 flex-col gap-1">
                            <label htmlFor="forma_pagamento" className="text-foreground text-sm font-medium">
                                Forma de pagamento
                            </label>

                            <select
                                id="forma_pagamento"
                                value={data.forma_pagamento}
                                onChange={(event) => setData('forma_pagamento', event.target.value)}
                                className={classeCampoFiltro}
                            >
                                <option value="">Todas</option>

                                {opcoes.formasPagamento.map((formaPagamento) => (
                                    <option key={formaPagamento} value={formaPagamento}>
                                        {formaPagamento}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-2 sm:mt-5 sm:flex-row sm:gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-primary text-primary-foreground w-full rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                        >
                            {processing ? 'Gerando relatório...' : 'Aplicar filtros'}
                        </button>

                        <button
                            type="button"
                            onClick={limparFiltros}
                            disabled={processing}
                            className="border-input bg-background text-foreground hover:bg-muted w-full rounded-lg border px-5 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                        >
                            Limpar filtros
                        </button>
                    </div>
                </form>

                <div className="border-border bg-card w-full min-w-0 max-w-full overflow-hidden rounded-2xl border p-4 shadow-sm sm:p-5">
                    <p className="text-muted-foreground text-sm font-medium">Período analisado</p>

                    <h2 className="text-foreground mt-1 text-xl font-bold">{periodoSelecionado.titulo}</h2>

                    <p className="text-muted-foreground mt-1 text-sm">
                        {formatarData(periodoSelecionado.inicio)}
                        {' até '}
                        {formatarData(periodoSelecionado.fim)}
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Total de entradas</p>

                        <p className="mt-3 text-2xl font-bold text-green-600">{formatarMoeda(resumo.entradas)}</p>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Total de despesas</p>

                        <p className="mt-3 text-2xl font-bold text-red-600">{formatarMoeda(resumo.despesas)}</p>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Saldo do período</p>

                        <p className={Number(resumo.saldo) >= 0 ? 'mt-3 text-2xl font-bold text-green-600' : 'mt-3 text-2xl font-bold text-red-600'}>
                            {formatarMoeda(resumo.saldo)}
                        </p>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Valor pendente</p>

                        <p className="mt-3 text-2xl font-bold text-amber-600">{formatarMoeda(resumo.valor_pendente)}</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Movimentações encontradas</p>

                        <p className="text-foreground mt-3 text-2xl font-bold">{resumo.quantidade}</p>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Concluídas</p>

                        <p className="mt-3 text-2xl font-bold text-green-600">{resumo.concluidas}</p>
                    </div>

                    <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                        <p className="text-muted-foreground text-sm font-medium">Pendentes</p>

                        <p className="mt-3 text-2xl font-bold text-amber-600">{resumo.pendentes}</p>
                    </div>
                </div>

                <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="mb-5">
                        <h2 className="text-foreground text-lg font-bold">Evolução financeira</h2>

                        <p className="text-muted-foreground mt-1 text-sm">Entradas e despesas agrupadas por {graficos.evolucao.granularidade}.</p>
                    </div>

                    <div className="h-80 w-full min-w-0 max-w-full overflow-hidden sm:h-96">
                        <ResponsiveContainer width="100%" height="100%">
                            <LineChart
                                data={graficos.evolucao.pontos}
                                margin={{
                                    top: 10,
                                    right: 12,
                                    left: -10,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />

                                <XAxis dataKey="rotulo" axisLine={false} tickLine={false} minTickGap={18} fontSize={12} />

                                <YAxis axisLine={false} tickLine={false} width={72} fontSize={12} tickFormatter={formatarValorCompacto} />

                                <Tooltip content={<TooltipFinanceiro />} />

                                {data.tipo === 'geral' && <Legend verticalAlign="top" height={36} />}

                                {mostrarEntradas && (
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

                                {mostrarDespesas && (
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

                <div className="border-border bg-card rounded-2xl border p-5 shadow-sm">
                    <div className="mb-5">
                        <h2 className="text-foreground text-lg font-bold">Valores por categoria</h2>

                        <p className="text-muted-foreground mt-1 text-sm">As dez categorias com maior movimentação no período.</p>
                    </div>

                    {categoriasGrafico.length === 0 ? (
                        <div className="py-12 text-center">
                            <p className="text-muted-foreground text-sm">Nenhum dado disponível para o período.</p>
                        </div>
                    ) : (
                        <div className="mx-auto h-64 w-full min-w-0 max-w-5xl overflow-hidden sm:h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={categoriasGrafico}
                                    barCategoryGap="35%"
                                    barGap={8}
                                    margin={{
                                        top: 10,
                                        right: 12,
                                        left: -10,
                                        bottom: 20,
                                    }}
                                >
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} />

                                    <XAxis
                                        dataKey="categoria"
                                        axisLine={false}
                                        tickLine={false}
                                        interval={0}
                                        angle={-20}
                                        textAnchor="end"
                                        height={80}
                                        fontSize={11}
                                    />

                                    <YAxis axisLine={false} tickLine={false} width={72} fontSize={12} tickFormatter={formatarValorCompacto} />

                                    <Tooltip content={<TooltipFinanceiro />} />

                                    {data.tipo === 'geral' && (
                                        <Legend verticalAlign="top" height={36} />
                                    )}

                                    {mostrarEntradas && (
                                        <Bar
                                            dataKey="entradas"
                                            name="Entradas"
                                            fill="#16a34a"
                                            maxBarSize={64}
                                            radius={[6, 6, 0, 0]}
                                        />
                                    )}

                                    {mostrarDespesas && (
                                        <Bar
                                            dataKey="despesas"
                                            name="Despesas"
                                            fill="#dc2626"
                                            maxBarSize={64}
                                            radius={[6, 6, 0, 0]}
                                        />
                                    )}
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </div>

                <div className="border-border bg-card w-full min-w-0 max-w-full overflow-hidden rounded-2xl border shadow-sm">
                    <div className="border-border border-b p-5">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-foreground text-lg font-bold">Movimentações do relatório</h2>

                                <p className="text-muted-foreground mt-1 text-sm">{movimentacoes.total} movimentações encontradas.</p>
                            </div>

                            <Link href="/movimentacoes" className="text-primary text-sm font-semibold hover:underline">
                                Ver todas as movimentações
                            </Link>
                        </div>
                    </div>

                    {movimentacoes.data.length === 0 ? (
                        <div className="p-10 text-center">
                            <p className="text-muted-foreground text-sm">Nenhuma movimentação encontrada com os filtros selecionados.</p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted text-muted-foreground">
                                        <tr>
                                            <th className="px-5 py-3 font-semibold">Data</th>
                                            <th className="px-5 py-3 font-semibold">Tipo</th>
                                            <th className="px-5 py-3 font-semibold">Descrição</th>
                                            <th className="px-5 py-3 font-semibold">Categoria</th>
                                            <th className="px-5 py-3 font-semibold">Pagamento</th>
                                            <th className="px-5 py-3 font-semibold">Status</th>
                                            <th className="px-5 py-3 text-right font-semibold">Valor</th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-border divide-y">
                                        {movimentacoes.data.map((movimentacao) => (
                                            <tr key={movimentacao.id} className="hover:bg-muted/50 transition">
                                                <td className="text-muted-foreground px-5 py-4 whitespace-nowrap">
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
                                                        {movimentacao.tipo === 'entrada' ? 'Entrada' : 'Despesa'}
                                                    </span>
                                                </td>

                                                <td className="min-w-56 px-5 py-4">
                                                    <Link
                                                        href={`/movimentacoes/${movimentacao.id}/edit`}
                                                        className="text-foreground hover:text-primary font-semibold hover:underline"
                                                    >
                                                        {movimentacao.descricao}
                                                    </Link>

                                                    {identificarLancamento(movimentacao) && (
                                                        <p className="mt-1 text-xs font-medium text-blue-600 dark:text-blue-400">
                                                            {identificarLancamento(movimentacao)}
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="text-muted-foreground px-5 py-4">{movimentacao.categoria || '-'}</td>

                                                <td className="text-muted-foreground px-5 py-4">{movimentacao.forma_pagamento || '-'}</td>

                                                <td className="px-5 py-4">
                                                    <span className={`rounded-full px-3 py-1 text-xs font-semibold ${classeStatus(movimentacao)}`}>
                                                        {formatarStatus(movimentacao)}
                                                    </span>
                                                </td>

                                                <td
                                                    className={
                                                        movimentacao.tipo === 'entrada'
                                                            ? 'px-5 py-4 text-right font-bold whitespace-nowrap text-green-600'
                                                            : 'px-5 py-4 text-right font-bold whitespace-nowrap text-red-600'
                                                    }
                                                >
                                                    {formatarMoeda(movimentacao.valor)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {movimentacoes.last_page > 1 && (
                                <div className="border-border flex flex-wrap items-center justify-between gap-4 border-t p-5">
                                    <p className="text-muted-foreground text-sm">
                                        Exibindo {movimentacoes.from ?? 0} até {movimentacoes.to ?? 0} de {movimentacoes.total}
                                    </p>

                                    <div className="flex flex-wrap gap-2">
                                        {movimentacoes.links.map((link, index) =>
                                            link.url ? (
                                                <Link
                                                    key={`${link.label}-${index}`}
                                                    href={link.url}
                                                    preserveScroll
                                                    className={
                                                        link.active
                                                            ? 'bg-primary text-primary-foreground rounded-lg px-3 py-2 text-sm font-semibold'
                                                            : 'border-input bg-background text-foreground hover:bg-muted rounded-lg border px-3 py-2 text-sm font-medium transition'
                                                    }
                                                >
                                                    {formatarLabelPaginacao(link.label)}
                                                </Link>
                                            ) : (
                                                <span
                                                    key={`${link.label}-${index}`}
                                                    className="border-input bg-muted text-muted-foreground cursor-not-allowed rounded-lg border px-3 py-2 text-sm opacity-60"
                                                >
                                                    {formatarLabelPaginacao(link.label)}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
