import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function VerifyEmail() {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout
            title="Verifique seu e-mail"
            description="Enviamos um link para o seu endereço de e-mail. Clique nele para confirmar sua conta."
        >
            <Head title="Verificação de e-mail" />

            <form onSubmit={submit} className="space-y-6 text-center">
                <Button
                    type="submit"
                    disabled={processing}
                    variant="secondary"
                >
                    {processing && (
                        <LoaderCircle className="h-4 w-4 animate-spin" />
                    )}

                    {processing
                        ? 'Enviando...'
                        : 'Reenviar e-mail de verificação'}
                </Button>

                <TextLink
                    href={route('logout')}
                    method="post"
                    className="mx-auto block text-sm"
                >
                    Sair da conta
                </TextLink>
            </form>
        </AuthLayout>
    );
}
