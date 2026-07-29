import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';

type CategoriaTipo = 'entrada' | 'despesa';

type FiltroCategoriaTipo = 'todos' | CategoriaTipo;

type Categoria = {
    id: number;
    tipo: CategoriaTipo;
    nome: string;
    ativo: boolean;
};

type Props = {
    categorias: Categoria[];
};

type FormData = {
    tipo: CategoriaTipo;
    nome: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categorias',
        href: '/categorias',
    },
];

function normalizarTexto(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

export default function CategoriasIndex({ categorias = [] }: Props) {
    const [categoriaEditando, setCategoriaEditando] =
        useState<Categoria | null>(null);

    const [categoriaParaExcluir, setCategoriaParaExcluir] =
        useState<Categoria | null>(null);

    const [excluindo, setExcluindo] = useState(false);

    const [buscaCategoria, setBuscaCategoria] = useState('');
    const [filtroTipo, setFiltroTipo] = useState<FiltroCategoriaTipo>('todos');

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
        tipo: 'despesa',
        nome: '',
    });

    function enviarFormulario(event: FormEvent) {
        event.preventDefault();

        if (categoriaEditando) {
            put(`/categorias/${categoriaEditando.id}`, {
                onSuccess: () => {
                    reset();
                    setCategoriaEditando(null);
                },
            });

            return;
        }

        post('/categorias', {
            onSuccess: () => {
                reset('nome');
            },
        });
    }

    function iniciarEdicao(categoria: Categoria) {
        setCategoriaEditando(categoria);
        setData('tipo', categoria.tipo);
        setData('nome', categoria.nome);
        clearErrors();

        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }

    function cancelarEdicao() {
        setCategoriaEditando(null);
        reset();
        clearErrors();
    }

    function excluirCategoria(categoria: Categoria) {
        setCategoriaParaExcluir(categoria);
    }

    function confirmarExclusaoCategoria() {
        if (!categoriaParaExcluir) {
            return;
        }

        const categoriaId = categoriaParaExcluir.id;

        setExcluindo(true);

        router.delete(`/categorias/${categoriaId}`, {
            preserveScroll: true,

            onSuccess: () => {
                if (categoriaEditando?.id === categoriaId) {
                    cancelarEdicao();
                }

                setCategoriaParaExcluir(null);
            },

            onFinish: () => {
                setExcluindo(false);
            },
        });
    }

    const categoriasFiltradas = useMemo(() => {
        const termo = normalizarTexto(buscaCategoria);

        return categorias.filter((categoria) => {
            const correspondeAoTipo =
                filtroTipo === 'todos' ||
                categoria.tipo === filtroTipo;

            const camposPesquisaveis = normalizarTexto(
                [
                    categoria.nome,
                    categoria.tipo,
                    categoria.tipo === 'entrada'
                        ? 'entrada receita'
                        : 'despesa gasto',
                    categoria.ativo
                        ? 'ativa ativo'
                        : 'inativa inativo',
                ].join(' ')
            );

            const correspondeÀBusca =
                termo === '' ||
                camposPesquisaveis.includes(termo);

            return correspondeAoTipo && correspondeÀBusca;
        });
    }, [categorias, buscaCategoria, filtroTipo]);

    const categoriasEntrada = categoriasFiltradas.filter(
        (categoria) => categoria.tipo === 'entrada'
    );

    const categoriasDespesa = categoriasFiltradas.filter(
        (categoria) => categoria.tipo === 'despesa'
    );

    const temFiltroAtivo =
        buscaCategoria.trim() !== '' ||
        filtroTipo !== 'todos';

    function limparFiltros() {
        setBuscaCategoria('');
        setFiltroTipo('todos');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categorias" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Categorias
                    </h1>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Cadastre e organize suas categorias de entradas e despesas.
                    </p>
                </div>

                <div className="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(260px,360px)_180px_auto] xl:items-end">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Pesquisar categoria
                        </label>

                        <input
                            type="search"
                            value={buscaCategoria}
                            onChange={(event) =>
                                setBuscaCategoria(event.target.value)
                            }
                            placeholder="Digite o nome"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tipo
                        </label>

                        <select
                            value={filtroTipo}
                            onChange={(event) =>
                                setFiltroTipo(
                                    event.target.value as FiltroCategoriaTipo
                                )
                            }
                            className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="todos">Todas</option>
                            <option value="despesa">Despesas</option>
                            <option value="entrada">Entradas</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        onClick={limparFiltros}
                        disabled={!temFiltroAtivo}
                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Limpar
                    </button>

                    <p className="text-sm text-gray-500 sm:col-span-2 xl:col-span-3 xl:text-right dark:text-gray-400">
                        {categoriasFiltradas.length}{' '}
                        {categoriasFiltradas.length === 1
                            ? 'categoria encontrada'
                            : 'categorias encontradas'}
                    </p>
                </div>
            </div>

                <form
                    onSubmit={enviarFormulario}
                    className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        {categoriaEditando ? 'Editar categoria' : 'Nova categoria'}
                    </h2>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tipo
                            </label>

                            <select
                                value={data.tipo}
                                onChange={(event) =>
                                    setData(
                                        'tipo',
                                        event.target.value as CategoriaTipo
                                    )
                                }
                                className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                                <option value="despesa">Despesa</option>
                                <option value="entrada">Entrada</option>
                            </select>

                            {errors.tipo && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.tipo}
                                </p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nome da categoria
                            </label>

                            <input
                                type="text"
                                value={data.nome}
                                onChange={(event) =>
                                    setData('nome', event.target.value)
                                }
                                placeholder="Ex: Mercado, Energia, Salário"
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            />

                            {errors.nome && (
                                <p className="mt-1 text-sm text-red-600">
                                    {errors.nome}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing
                                ? categoriaEditando
                                    ? 'Atualizando...'
                                    : 'Salvando...'
                                : categoriaEditando
                                  ? 'Salvar alteração'
                                  : 'Salvar categoria'}
                        </button>

                        {categoriaEditando && (
                            <button
                                type="button"
                                onClick={cancelarEdicao}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancelar
                            </button>
                        )}
                    </div>
                </form>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Categorias de despesa
                            </h2>
                        </div>

                        {categoriasDespesa.length === 0 ? (
                            <div className="p-5 text-sm text-gray-500 dark:text-gray-400">
                                {temFiltroAtivo
                                ? 'Nenhuma categoria de despesa encontrada com os filtros selecionados.'
                                : 'Nenhuma categoria de despesa cadastrada.' }
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {categoriasDespesa.map((categoria) => (
                                    <div
                                        key={categoria.id}
                                        className="flex items-center justify-between gap-4 p-4"
                                    >
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {categoria.nome}
                                        </span>

                                        <div className="flex gap-2">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    iniciarEdicao(categoria)
                                                }
                                                className="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-400 dark:hover:bg-blue-950"
                                            >
                                                Editar
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    excluirCategoria(categoria)
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

                    <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Categorias de entrada
                            </h2>
                        </div>

                        {categoriasEntrada.length === 0 ? (
                            <div className="p-5 text-sm text-gray-500 dark:text-gray-400">
                                { temFiltroAtivo
                                ? 'Nenhuma categoria de entrada encontrada com os filtros selecionados.'
                                : 'Nenhuma categoria de entrada cadastrada.'}
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                {categoriasEntrada.map((categoria) => (
                                    <div
                                        key={categoria.id}
                                        className="flex items-center justify-between gap-4 p-4"
                                    >
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {categoria.nome}
                                        </span>

                                        <div className="flex gap-2">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    iniciarEdicao(categoria)
                                                }
                                                className="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-400 dark:hover:bg-blue-950"
                                            >
                                                Editar
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    excluirCategoria(categoria)
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
            </div>
        {categoriaParaExcluir && (
            <ConfirmDialog
                open
                title="Excluir categoria"
                description={`Deseja realmente excluir a categoria "${categoriaParaExcluir.nome}"? Confirme para continuar.`}
                confirmLabel="Excluir categoria"
                variant="danger"
                processing={excluindo}
                onConfirm={confirmarExclusaoCategoria}
                onClose={() => setCategoriaParaExcluir(null)}
            />
        )}
        </AppLayout>
    );
}
