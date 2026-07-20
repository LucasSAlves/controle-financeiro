import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

type PreferenciasNotificacao = {
    receber_aviso_email: boolean;
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

export default function Notificacoes({
    preferencias,
}: NotificacoesProps) {
    const {
        data,
        setData,
        patch,
        errors,
        processing,
    } = useForm({
        receber_aviso_email:
            preferencias.receber_aviso_email ?? true,
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

                    <form
                        onSubmit={submit}
                        className="space-y-6"
                    >
                        <div className="space-y-4">
                            <label
                                htmlFor="receber_aviso_email"
                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition hover:bg-neutral-50 dark:hover:bg-neutral-900"
                            >
                                <input
                                    id="receber_aviso_email"
                                    type="checkbox"
                                    checked={
                                        data.receber_aviso_email
                                    }
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
                                        Os avisos serão enviados para
                                        o e-mail cadastrado na sua
                                        conta.
                                    </p>
                                </div>
                            </label>

                            <div className="flex items-start gap-3 rounded-lg border border-dashed bg-neutral-50 p-4 opacity-70 dark:bg-neutral-900">
                                <input
                                    id="receber_aviso_whatsapp"
                                    type="checkbox"
                                    checked={false}
                                    disabled
                                    readOnly
                                    className="mt-1 h-4 w-4 cursor-not-allowed rounded border-neutral-300"
                                />

                                <div className="flex-1 space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">
                                            Receber avisos pelo
                                            WhatsApp
                                        </p>

                                        <span className="rounded-full bg-neutral-200 px-2.5 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                            Indisponível no momento
                                        </span>
                                    </div>

                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        A integração com o WhatsApp
                                        está temporariamente
                                        indisponível. Os avisos
                                        continuam funcionando
                                        normalmente por e-mail.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <InputError
                            message={
                                errors.receber_aviso_email
                            }
                        />

                        {!data.receber_aviso_email && (
                            <div className="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-900 dark:bg-yellow-950 dark:text-yellow-200">
                                Você não receberá avisos de
                                vencimento enquanto o envio por
                                e-mail estiver desativado.
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                {processing
                                    ? 'Salvando...'
                                    : 'Salvar preferências'}
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
