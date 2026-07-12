import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BarChart3, Bell, CheckCircle2, CreditCard } from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Controle Financeiro" />

            <main className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 text-gray-900">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-green-500 text-lg font-bold text-white shadow-md">
                            CF
                        </div>

                        <div>
                            <p className="text-lg font-bold leading-none text-gray-900">
                                Controle Financeiro
                            </p>
                            <p className="text-xs text-gray-500">
                                Sistema pessoal/doméstico
                            </p>
                        </div>
                    </div>

                    <nav className="flex items-center gap-3">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                Acessar Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-xl px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-white hover:shadow-sm"
                                >
                                    Entrar
                                </Link>

                                <Link
                                    href={route('register')}
                                    className="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                                >
                                    Criar conta
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <section className="mx-auto grid w-full max-w-6xl items-center gap-10 px-6 py-12 lg:grid-cols-2 lg:py-20">
                    <div>
                        <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white px-4 py-2 text-sm font-medium text-blue-700 shadow-sm">
                            <Bell className="h-4 w-4" />
                            Avisos automáticos de vencimento por e-mail
                        </div>

                        <h1 className="text-4xl font-extrabold tracking-tight text-gray-950 md:text-5xl">
                            Organize suas contas, despesas e receitas em um só lugar.
                        </h1>

                        <p className="mt-5 max-w-xl text-lg leading-8 text-gray-600">
                            Controle suas movimentações financeiras, acompanhe parcelas,
                            veja contas vencidas e receba avisos no vencimento das despesas.
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700"
                                >
                                    Ir para o Dashboard
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-700"
                                    >
                                        Entrar no sistema
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>

                                    <Link
                                        href={route('register')}
                                        className="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    >
                                        Criar conta grátis
                                    </Link>
                                </>
                            )}
                        </div>

                        <div className="mt-8 grid gap-3 sm:grid-cols-2">
                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                                Dashboard com valores reais
                            </div>

                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                                Controle de contas pendentes
                            </div>

                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                                Compras parceladas
                            </div>

                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                                Avisos de vencimento
                            </div>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-gray-200 bg-white p-6 shadow-xl">
                        <div className="mb-5 flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500">
                                    Resumo financeiro
                                </p>
                                <h2 className="text-2xl font-bold text-gray-900">
                                    Visão do mês
                                </h2>
                            </div>

                            <div className="rounded-2xl bg-blue-50 p-3 text-blue-600">
                                <BarChart3 className="h-6 w-6" />
                            </div>
                        </div>

                        <div className="grid gap-4">
                            <div className="rounded-2xl border border-green-100 bg-green-50 p-5">
                                <p className="text-sm font-medium text-green-700">
                                    Entradas
                                </p>
                                <p className="mt-2 text-2xl font-bold text-green-700">
                                    R$ 3.500,00
                                </p>
                            </div>

                            <div className="rounded-2xl border border-red-100 bg-red-50 p-5">
                                <p className="text-sm font-medium text-red-700">
                                    Despesas
                                </p>
                                <p className="mt-2 text-2xl font-bold text-red-700">
                                    R$ 1.280,00
                                </p>
                            </div>

                            <div className="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                                <p className="text-sm font-medium text-blue-700">
                                    Saldo previsto
                                </p>
                                <p className="mt-2 text-2xl font-bold text-blue-700">
                                    R$ 2.220,00
                                </p>
                            </div>

                            <div className="rounded-2xl border border-yellow-100 bg-yellow-50 p-5">
                                <div className="flex items-center gap-3">
                                    <div className="rounded-xl bg-yellow-100 p-2 text-yellow-700">
                                        <CreditCard className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-bold text-yellow-800">
                                            2 contas vencem hoje
                                        </p>
                                        <p className="text-xs text-yellow-700">
                                            Receba aviso automático por e-mail.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
