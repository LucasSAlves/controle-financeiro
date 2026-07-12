import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type FormaPagamento = {
    id: number;
    nome: string;
    ativo: boolean;
};

type Props = {
    formasPagamento: FormaPagamento[];
};

type FormData = {
    nome: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Formas de pagamento',
        href: '/formas-pagamento',
    },
];

export default function FormasPagamentoIndex({
    formasPagamento = [],
}: Props) {
    const [formaEditando, setFormaEditando] =
        useState<FormaPagamento | null>(null);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<FormData>({
        nome: '',
    });

    function enviarFormulario(event: FormEvent) {
        event.preventDefault();

        if (formaEditando) {
            put(`/formas-pagamento/${formaEditando.id}`, {
                onSuccess: () => {
                    reset('nome');
                    setFormaEditando(null);
                },
            });

            return;
        }

        post('/formas-pagamento', {
            onSuccess: () => {
                reset('nome');
            },
        });
    }

    function iniciarEdicao(formaPagamento: FormaPagamento) {
        setFormaEditando(formaPagamento);
        setData('nome', formaPagamento.nome);
        clearErrors();

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }

    function cancelarEdicao() {
        setFormaEditando(null);
        reset('nome');
        clearErrors();
    }

    function excluirFormaPagamento(id: number) {
        const confirmar = window.confirm(
            'Tem certeza que deseja excluir esta forma de pagamento?'
        );

        if (!confirmar) {
            return;
        }

        router.delete(`/formas-pagamento/${id}`, {
            onSuccess: () => {
                if (formaEditando?.id === id) {
                    cancelarEdicao();
                }
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Formas de pagamento" />

            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Formas de pagamento
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Cadastre as formas de pagamento que você usa no dia a dia.
                    </p>
                </div>

                <form
                    onSubmit={enviarFormulario}
                    className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        {formaEditando
                            ? 'Editar forma de pagamento'
                            : 'Nova forma de pagamento'}
                    </h2>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nome
                            </label>

                            <input
                                type="text"
                                value={data.nome}
                                onChange={(event) =>
                                    setData('nome', event.target.value)
                                }
                                placeholder="Ex: Pix, Dinheiro, Nubank Crédito"
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.nome && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.nome}
                                </p>
                            )}
                        </div>

                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                            >
                                {processing
                                    ? formaEditando
                                        ? 'Atualizando...'
                                        : 'Salvando...'
                                    : formaEditando
                                      ? 'Salvar alteração'
                                      : 'Salvar forma de pagamento'}
                            </button>

                            {formaEditando && (
                                <button
                                    type="button"
                                    onClick={cancelarEdicao}
                                    className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                >
                                    Cancelar
                                </button>
                            )}
                        </div>
                    </div>
                </form>

                <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Formas cadastradas
                        </h2>
                    </div>

                    {formasPagamento.length === 0 ? (
                        <div className="p-5 text-sm text-gray-500 dark:text-gray-400">
                            Nenhuma forma de pagamento cadastrada.
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200 dark:divide-gray-700">
                            {formasPagamento.map((formaPagamento) => (
                                <div
                                    key={formaPagamento.id}
                                    className="flex items-center justify-between gap-4 p-4"
                                >
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {formaPagamento.nome}
                                    </span>

                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                iniciarEdicao(formaPagamento)
                                            }
                                            className="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-400 dark:hover:bg-blue-950"
                                        >
                                            Editar
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                excluirFormaPagamento(
                                                    formaPagamento.id
                                                )
                                            }
                                            className="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                        >
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
