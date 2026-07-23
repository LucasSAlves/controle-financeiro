import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CheckCircle2,
    Circle,
    FlaskConical,
    LayoutDashboard,
    ListChecks,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Testes do sistema',
        href: '/testes',
    },
];

const gruposDeTeste = [
    {
        numero: 1,
        titulo: 'Movimentação normal',
        descricao: 'Cadastrar uma entrada e uma despesa sem parcelamento ou recorrência.',
        status: 'proximo',
    },
    {
        numero: 2,
        titulo: 'Despesa parcelada',
        descricao:
            'Criar, aumentar, reduzir, preservar parcelas pagas e excluir parcelas futuras.',
        status: 'aguardando',
    },
    {
        numero: 3,
        titulo: 'Despesa fixa mensal',
        descricao:
            'Testar geração, alterações, pagamentos, exclusões e encerramento da recorrência.',
        status: 'aguardando',
    },
    {
        numero: 4,
        titulo: 'Entrada fixa mensal',
        descricao:
            'Testar geração automática, alterações, exclusões e encerramento da recorrência.',
        status: 'aguardando',
    },
    {
        numero: 5,
        titulo: 'Dashboard, totais e filtros',
        descricao:
            'Conferir valores, filtros mensais, tipos, status e informações do Dashboard.',
        status: 'aguardando',
    },
    {
        numero: 6,
        titulo: 'Teste de duplicações',
        descricao:
            'Recarregar os meses e acessar as páginas repetidamente sem duplicar lançamentos.',
        status: 'aguardando',
    },
    {
        numero: 7,
        titulo: 'Conferência pelo Tinker',
        descricao:
            'Consultar movimentações, recorrências, parcelas e exceções diretamente no banco.',
        status: 'aguardando',
    },
];

export default function TestesIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Testes do sistema" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                <section className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="flex items-start gap-4">
                            <div className="rounded-xl bg-blue-100 p-3 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                <FlaskConical className="h-7 w-7" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Central de testes
                                </h1>

                                <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                    Página exclusiva do ambiente local para validar as
                                    funcionalidades do Sistema de Controle Financeiro antes da
                                    publicação.
                                </p>
                            </div>
                        </div>

                        <div className="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                            Ambiente local
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2">
                    <Link
                        href="/movimentacoes"
                        className="group flex items-center justify-between rounded-xl border bg-card p-5 shadow-sm transition hover:border-blue-400 hover:shadow-md"
                    >
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-muted p-3">
                                <ListChecks className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="font-semibold">Abrir movimentações</p>
                                <p className="text-sm text-muted-foreground">
                                    Criar e conferir os lançamentos dos testes.
                                </p>
                            </div>
                        </div>

                        <ArrowRight className="h-5 w-5 transition group-hover:translate-x-1" />
                    </Link>

                    <Link
                        href="/dashboard"
                        className="group flex items-center justify-between rounded-xl border bg-card p-5 shadow-sm transition hover:border-blue-400 hover:shadow-md"
                    >
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-muted p-3">
                                <LayoutDashboard className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="font-semibold">Abrir Dashboard</p>
                                <p className="text-sm text-muted-foreground">
                                    Conferir totais, saldo e avisos.
                                </p>
                            </div>
                        </div>

                        <ArrowRight className="h-5 w-5 transition group-hover:translate-x-1" />
                    </Link>
                </section>

                <section className="rounded-xl border bg-card shadow-sm">
                    <div className="border-b px-6 py-4">
                        <h2 className="text-lg font-semibold">Roteiro de validação</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Os testes serão executados individualmente e conferidos antes da
                            próxima etapa.
                        </p>
                    </div>

                    <div className="divide-y">
                        {gruposDeTeste.map((teste) => (
                            <div
                                key={teste.numero}
                                className="flex items-start gap-4 px-6 py-5"
                            >
                                <div className="mt-0.5">
                                    {teste.status === 'concluido' ? (
                                        <CheckCircle2 className="h-6 w-6 text-green-600" />
                                    ) : teste.status === 'proximo' ? (
                                        <FlaskConical className="h-6 w-6 text-blue-600" />
                                    ) : (
                                        <Circle className="h-6 w-6 text-muted-foreground" />
                                    )}
                                </div>

                                <div className="flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="font-semibold">
                                            {teste.numero}. {teste.titulo}
                                        </h3>

                                        {teste.status === 'proximo' && (
                                            <span className="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                                Próximo teste
                                            </span>
                                        )}

                                        {teste.status === 'aguardando' && (
                                            <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                                Aguardando
                                            </span>
                                        )}
                                    </div>

                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {teste.descricao}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/40">
                    <h2 className="font-semibold text-blue-900 dark:text-blue-100">
                        Primeiro teste
                    </h2>

                    <p className="mt-1 text-sm text-blue-800 dark:text-blue-200">
                        Criar uma entrada normal e uma despesa normal, sem parcelamento e
                        sem recorrência.
                    </p>
                </section>
            </div>
        </AppLayout>
    );
}
