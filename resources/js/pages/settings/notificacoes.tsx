import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

type PreferenciasNotificacao = {
    receber_aviso_email: boolean;
    receber_aviso_whatsapp: boolean;
    telefone_whatsapp: string | null;
};

type NotificacoesProps = {
    preferencias: PreferenciasNotificacao;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configurações de notificações',
        href: '/settings/notificacoes',
    },
];

export default function Notificacoes({ preferencias }: NotificacoesProps) {
    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            receber_aviso_email:
                preferencias.receber_aviso_email ?? true,

            receber_aviso_whatsapp:
                preferencias.receber_aviso_whatsapp ?? false,

            telefone_whatsapp:
                preferencias.telefone_whatsapp ?? '',
        });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('notificacoes.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configurações de notificações" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Avisos de vencimento"
                        description="Escolha como deseja receber os avisos das suas despesas."
                    />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="space-y-4">
                            <label
                                htmlFor="receber_aviso_email"
                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-900"
                            >
                                <input
                                    id="receber_aviso_email"
                                    type="checkbox"
                                    checked={data.receber_aviso_email}
                                    onChange={(event) =>
                                        setData(
                                            'receber_aviso_email',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 cursor-pointer rounded border-neutral-300"
                                />

                                <div className="space-y-1">
                                    <p className="font-medium">
                                        Receber avisos por e-mail
                                    </p>

                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Os avisos serão enviados para o e-mail
                                        cadastrado na sua conta.
                                    </p>
                                </div>
                            </label>

                            <label
                                htmlFor="receber_aviso_whatsapp"
                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-900"
                            >
                                <input
                                    id="receber_aviso_whatsapp"
                                    type="checkbox"
                                    checked={data.receber_aviso_whatsapp}
                                    onChange={(event) =>
                                        setData(
                                            'receber_aviso_whatsapp',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 cursor-pointer rounded border-neutral-300"
                                />

                                <div className="space-y-1">
                                    <p className="font-medium">
                                        Receber avisos pelo WhatsApp
                                    </p>

                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Ao ativar esta opção, você autoriza o
                                        envio de avisos de vencimento para o
                                        número informado.
                                    </p>
                                </div>
                            </label>
                        </div>

                        {data.receber_aviso_whatsapp && (
                            <div className="grid gap-2">
                                <Label htmlFor="telefone_whatsapp">
                                    Número do WhatsApp
                                </Label>

                                <Input
                                    id="telefone_whatsapp"
                                    type="tel"
                                    value={data.telefone_whatsapp}
                                    onChange={(event) =>
                                        setData(
                                            'telefone_whatsapp',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="(11) 99999-9999"
                                    autoComplete="tel"
                                    required
                                />

                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Informe o número com DDD. O código do Brasil
                                    será acrescentado automaticamente.
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.telefone_whatsapp}
                                />
                            </div>
                        )}

                        <InputError
                            message={errors.receber_aviso_email}
                        />

                        <InputError
                            message={errors.receber_aviso_whatsapp}
                        />

                        {!data.receber_aviso_email &&
                            !data.receber_aviso_whatsapp && (
                                <div className="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-900 dark:bg-yellow-950 dark:text-yellow-200">
                                    Você não receberá avisos de vencimento
                                    enquanto as duas opções estiverem
                                    desativadas.
                                </div>
                            )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                Salvar preferências
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Preferências salvas
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
