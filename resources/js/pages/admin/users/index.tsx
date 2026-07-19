import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type SharedData,
} from '@/types';
import {
    Head,
    Link,
    router,
    usePage,
} from '@inertiajs/react';
import {
    Ban,
    CalendarDays,
    CheckCircle2,
    Crown,
    LoaderCircle,
    LockKeyhole,
    RotateCcw,
    ShieldCheck,
    UserRound,
    Users,
    UserMinus,
    UserPlus,
} from 'lucide-react';
import { useState } from 'react';

type User = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    is_active: boolean;
    is_primary_admin: boolean;
    created_at: string;
};

type PaginatedUsers = {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    users: PaginatedUsers;
    status?: string;
    errors?: {
        user?: string;
    };
};

type UserActionsProps = {
    user: User;
    currentUserId: number;
    processingUserId: number | null;
    onBlock: (user: User) => void;
    onActivate: (user: User) => void;
    onPromote: (user: User) => void;
    onDemote: (user: User) => void;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administração',
        href: '/admin/usuarios',
    },
    {
        title: 'Usuários',
        href: '/admin/usuarios',
    },
];

function formatarData(data: string): string {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(data));
}

function TipoUsuario({ user }: { user: User }) {
    if (user.is_primary_admin) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                <Crown className="h-3.5 w-3.5" />
                Administrador principal
            </span>
        );
    }

    if (user.is_admin) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                <ShieldCheck className="h-3.5 w-3.5" />
                Administrador
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <UserRound className="h-3.5 w-3.5" />
            Usuário comum
        </span>
    );
}

function StatusUsuario({ ativo }: { ativo: boolean }) {
    if (ativo) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Ativo
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">
            <Ban className="h-3.5 w-3.5" />
            Bloqueado
        </span>
    );
}

function UserActions({
    user,
    currentUserId,
    processingUserId,
    onBlock,
    onActivate,
    onPromote,
    onDemote,
}: UserActionsProps) {
    const processing = processingUserId === user.id;

    if (user.is_primary_admin) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-300">
                <Crown className="h-3.5 w-3.5" />
                Protegido
            </span>
        );
    }

    if (user.id === currentUserId) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <UserRound className="h-3.5 w-3.5" />
                Sua conta
            </span>
        );
    }

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {user.is_active ? (
                <button
                    type="button"
                    onClick={() => onBlock(user)}
                    disabled={processing}
                    className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950"
                >
                    {processing ? (
                        <LoaderCircle className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <LockKeyhole className="h-3.5 w-3.5" />
                    )}

                    Bloquear acesso
                </button>
            ) : (
                <button
                    type="button"
                    onClick={() => onActivate(user)}
                    disabled={processing}
                    className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-green-300 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-green-800 dark:text-green-400 dark:hover:bg-green-950"
                >
                    {processing ? (
                        <LoaderCircle className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <RotateCcw className="h-3.5 w-3.5" />
                    )}

                    Reativar acesso
                </button>
            )}

            {user.is_admin ? (
                <button
                    type="button"
                    onClick={() => onDemote(user)}
                    disabled={processing}
                    className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-orange-300 px-3 py-2 text-xs font-semibold text-orange-700 transition hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-950"
                >
                    {processing ? (
                        <LoaderCircle className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <UserMinus className="h-3.5 w-3.5" />
                    )}

                    Remover administrador
                </button>
            ) : (
                <button
                    type="button"
                    onClick={() => onPromote(user)}
                    disabled={processing}
                    className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-blue-300 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                >
                    {processing ? (
                        <LoaderCircle className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <UserPlus className="h-3.5 w-3.5" />
                    )}

                    Promover a administrador
                </button>
            )}
        </div>
    );
}

export default function AdminUsersIndex({
    users,
    status,
    errors,
}: Props) {
    const { auth } = usePage<SharedData>().props;
    const [processingUserId, setProcessingUserId] =
        useState<number | null>(null);

    function bloquearUsuario(user: User) {
        const confirmar = window.confirm(
            `Deseja bloquear o acesso de ${user.name}?`,
        );

        if (!confirmar) {
            return;
        }

        setProcessingUserId(user.id);

        router.patch(
            `/admin/usuarios/${user.id}/bloquear`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingUserId(null),
            },
        );
    }

    function reativarUsuario(user: User) {
        const confirmar = window.confirm(
            `Deseja reativar o acesso de ${user.name}?`,
        );

        if (!confirmar) {
            return;
        }

        setProcessingUserId(user.id);

        router.patch(
            `/admin/usuarios/${user.id}/reativar`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingUserId(null),
            },
        );
    }

    function promoverUsuario(user: User) {
        const confirmar = window.confirm(
            `Deseja promover ${user.name} a administrador?`,
        );

        if (!confirmar) {
            return;
        }

        setProcessingUserId(user.id);

        router.patch(
            `/admin/usuarios/${user.id}/promover`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingUserId(null),
            },
        );
    }

    function removerAdministrador(user: User) {
        const confirmar = window.confirm(
            `Deseja remover a permissão de administrador de ${user.name}?`,
        );

        if (!confirmar) {
            return;
        }

        setProcessingUserId(user.id);

        router.patch(
            `/admin/usuarios/${user.id}/remover-administrador`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingUserId(null),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuários - Administração" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                <Users className="h-5 w-5" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Usuários
                                </h1>

                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Gerencie os usuários cadastrados no sistema.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Total de usuários
                        </p>

                        <p className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                            {users.total}
                        </p>
                    </div>
                </div>

                {status && (
                    <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-950 dark:text-green-300">
                        {status}
                    </div>
                )}

                {errors?.user && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                        {errors.user}
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Usuários cadastrados
                        </h2>

                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Visualize o tipo e controle o acesso de cada conta.
                        </p>
                    </div>

                    {users.data.length === 0 ? (
                        <div className="p-8 text-center">
                            <Users className="mx-auto h-10 w-10 text-gray-400" />

                            <p className="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nenhum usuário cadastrado.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-950">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Usuário
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Tipo
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Situação
                                            </th>

                                            <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Cadastro
                                            </th>

                                            <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                Ações
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {users.data.map((user) => (
                                            <tr
                                                key={user.id}
                                                className="transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                            >
                                                <td className="px-5 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                            {user.name
                                                                .charAt(0)
                                                                .toUpperCase()}
                                                        </div>

                                                        <div>
                                                            <p className="font-medium text-gray-900 dark:text-white">
                                                                {user.name}
                                                            </p>

                                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                                {user.email}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4">
                                                    <TipoUsuario user={user} />
                                                </td>

                                                <td className="px-5 py-4">
                                                    <StatusUsuario
                                                        ativo={user.is_active}
                                                    />
                                                </td>

                                                <td className="px-5 py-4">
                                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <CalendarDays className="h-4 w-4" />

                                                        {formatarData(
                                                            user.created_at,
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    <UserActions
                                                        user={user}
                                                        currentUserId={
                                                            auth.user.id
                                                        }
                                                        processingUserId={
                                                            processingUserId
                                                        }
                                                        onBlock={
                                                            bloquearUsuario
                                                        }
                                                        onActivate={
                                                            reativarUsuario
                                                        }
                                                        onPromote={promoverUsuario}
                                                        onDemote={removerAdministrador}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-gray-200 md:hidden dark:divide-gray-700">
                                {users.data.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex flex-col gap-4 p-5"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {user.name
                                                    .charAt(0)
                                                    .toUpperCase()}
                                            </div>

                                            <div className="min-w-0">
                                                <p className="truncate font-medium text-gray-900 dark:text-white">
                                                    {user.name}
                                                </p>

                                                <p className="truncate text-sm text-gray-500 dark:text-gray-400">
                                                    {user.email}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <TipoUsuario user={user} />

                                            <StatusUsuario
                                                ativo={user.is_active}
                                            />
                                        </div>

                                        <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                            <CalendarDays className="h-4 w-4" />
                                            Cadastrado em{' '}
                                            {formatarData(user.created_at)}
                                        </div>

                                        <div>
                                            <UserActions
                                                user={user}
                                                currentUserId={auth.user.id}
                                                processingUserId={
                                                    processingUserId
                                                }
                                                onBlock={bloquearUsuario}
                                                onActivate={reativarUsuario}
                                                onPromote={promoverUsuario}
                                                onDemote={removerAdministrador}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </>
                    )}

                    {users.last_page > 1 && (
                        <div className="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Exibindo {users.from ?? 0} até {users.to ?? 0} de{' '}
                                {users.total} usuários
                            </p>

                            <div className="flex items-center gap-2">
                                {users.prev_page_url ? (
                                    <Link
                                        href={users.prev_page_url}
                                        preserveScroll
                                        className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        Anterior
                                    </Link>
                                ) : (
                                    <span className="cursor-not-allowed rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400 dark:border-gray-800">
                                        Anterior
                                    </span>
                                )}

                                <span className="px-2 text-sm text-gray-600 dark:text-gray-400">
                                    Página {users.current_page} de{' '}
                                    {users.last_page}
                                </span>

                                {users.next_page_url ? (
                                    <Link
                                        href={users.next_page_url}
                                        preserveScroll
                                        className="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                    >
                                        Próxima
                                    </Link>
                                ) : (
                                    <span className="cursor-not-allowed rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400 dark:border-gray-800">
                                        Próxima
                                    </span>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
