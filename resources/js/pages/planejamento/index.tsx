import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarRange, ChevronDown } from 'lucide-react';
import { useState } from 'react';

type Lancamento = {
    id: number | null;
    descricao: string;
    valor: number;
    tipo: 'entrada' | 'despesa';
    data: string;
    data_original: string;
    realizado: boolean;
};

type Mes = {
    competencia: string;
    saldo_inicial: number;
    entradas: number;
    despesas: number;
    saldo_final: number;
    lancamentos: Lancamento[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Planejamento', href: '/planejamento' }];
const dinheiro = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const moeda = (centavos: number) => dinheiro.format(centavos / 100);
const data = (valor: string) => valor.split('-').reverse().join('/');
const nomeMes = (mes: string) =>
    new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(mes + '-01T00:00:00Z'));
const painel = 'rounded-2xl border border-border bg-card p-4 shadow-sm sm:p-5';

function LinhaPendencia({ item }: { item: Lancamento }) {
    const form = useForm({});

    return (
        <li className="flex flex-wrap items-center justify-between gap-3 py-3">
            <div>
                <p className="font-medium">{item.descricao}</p>
                <p className="text-sm text-muted-foreground">Venceu em {data(item.data_original)}</p>
                {form.hasErrors && <p role="alert" className="text-sm text-destructive">Não foi possível registrar o pagamento.</p>}
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <span className="font-semibold">{moeda(item.valor)}</span>
                {item.id === null ? (
                    <Link href="/movimentacoes" className="text-sm font-medium text-primary">Ver movimentações</Link>
                ) : (
                    <button
                        type="button"
                        disabled={form.processing}
                        className="rounded-lg border border-border px-3 py-2 text-sm font-medium hover:bg-muted disabled:opacity-60"
                        onClick={() => {
                            if (window.confirm('Registrar o pagamento de ' + moeda(item.valor) + ' com a data de hoje?')) {
                                form.patch('/movimentacoes/' + item.id + '/marcar-como-pago', { preserveScroll: true });
                            }
                        }}
                    >
                        {form.processing ? 'Registrando…' : 'Marcar como paga'}
                    </button>
                )}
            </div>
        </li>
    );
}

export default function PrevisaoFinanceira({ meses, pendencias }: { meses: Mes[]; pendencias: Record<'recentes' | 'antigas', Lancamento[]> }) {
    const [aba, selecionarAba] = useState<'recentes' | 'antigas'>('recentes');
    const soma = (itens: Lancamento[]) => itens.reduce((total, item) => total + item.valor, 0);
    const antigas = soma(pendencias.antigas);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Previsão Financeira" />
            <div className="flex min-w-0 flex-col gap-4 p-4 sm:gap-5 sm:p-5">
                <header className={painel}>
                    <div className="mb-2 flex items-center gap-2 text-primary">
                        <CalendarRange className="size-5" aria-hidden="true" />
                        <span className="text-sm font-semibold">Planejamento</span>
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Previsão Financeira</h1>
                    <p className="mt-1 text-sm text-muted-foreground">Veja o saldo estimado deste mês e dos próximos dois com base nos seus lançamentos.</p>
                </header>

                <section aria-label="Projeção mensal" className="grid min-w-0 items-start gap-4 lg:grid-cols-3">
                    {meses.map((mes) => (
                        <article key={mes.competencia} className={painel}>
                            <h2 className="text-sm font-semibold capitalize">{nomeMes(mes.competencia)}</h2>
                            <p className="mt-3 text-xs text-muted-foreground">Saldo final estimado</p>
                            <p className={'break-words text-2xl font-semibold tabular-nums ' + (mes.saldo_final < 0 ? 'text-destructive' : 'text-primary')}>
                                {moeda(mes.saldo_final)}
                            </p>
                            {mes.saldo_final < 0 && <p className="text-sm text-destructive">Déficit previsto</p>}
                            <dl className="mt-4 space-y-2 text-sm">
                                {([
                                    ['Saldo inicial', mes.saldo_inicial],
                                    ['Entradas', mes.entradas],
                                    ['Despesas', mes.despesas],
                                ] as const).map(([label, valor]) => (
                                    <div key={label} className="flex justify-between gap-2">
                                        <dt className="text-muted-foreground">{label}</dt>
                                        <dd className="font-medium tabular-nums">{moeda(valor)}</dd>
                                    </div>
                                ))}
                            </dl>
                            <details className="mt-4 border-t border-border pt-3">
                                <summary className="flex cursor-pointer list-none items-center justify-between text-sm font-medium">
                                    Ver lançamentos <ChevronDown className="size-4" aria-hidden="true" />
                                </summary>
                                {mes.lancamentos.length === 0 && <p className="mt-3 text-sm text-muted-foreground">Nenhum lançamento no período.</p>}
                                <ul className="divide-y divide-border">
                                    {mes.lancamentos.map((item, index) => (
                                        <li key={item.id ?? 'previsto-' + index} className="flex justify-between gap-2 py-2 text-sm">
                                            <div className="min-w-0">
                                                <p className="break-words font-medium">{item.descricao}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {data(item.data)} · {item.realizado ? 'Realizado' : 'Previsto'}
                                                </p>
                                            </div>
                                            <span className="shrink-0 tabular-nums">{moeda(item.tipo === 'despesa' ? -item.valor : item.valor)}</span>
                                        </li>
                                    ))}
                                </ul>
                            </details>
                        </article>
                    ))}
                </section>

                <section className={painel} aria-labelledby="atrasos">
                    <h2 id="atrasos" className="text-lg font-semibold">Contas em atraso</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {moeda(soma(pendencias.recentes) + antigas)} em atraso, incluindo {moeda(antigas)} em pendências antigas. Esses valores ficam separados do saldo projetado até o pagamento.
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2" aria-label="Filtrar contas em atraso">
                        {(['recentes', 'antigas'] as const).map((grupo) => (
                            <button
                                key={grupo}
                                type="button"
                                aria-pressed={aba === grupo}
                                className={'rounded-lg px-3 py-2 text-sm font-medium ' + (aba === grupo ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground')}
                                onClick={() => selecionarAba(grupo)}
                            >
                                {grupo === 'recentes' ? 'Atrasos recentes' : 'Pendências antigas'} ({pendencias[grupo].length})
                            </button>
                        ))}
                    </div>
                    {pendencias[aba].length === 0 && <p className="mt-4 text-sm text-muted-foreground">Nenhuma conta nesta lista.</p>}
                    <ul className="divide-y divide-border">
                        {pendencias[aba].map((item, index) => <LinhaPendencia key={item.id ?? 'previsto-' + index} item={item} />)}
                    </ul>
                </section>
            </div>
        </AppLayout>
    );
}
