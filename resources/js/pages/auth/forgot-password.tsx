import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    LoaderCircle,
    Mail,
    WalletCards,
} from 'lucide-react';
import { FormEventHandler } from 'react';

interface ForgotPasswordForm {
    [key: string]: string;
    email: string;
}

interface ForgotPasswordProps {
    status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } =
        useForm<ForgotPasswordForm>({
            email: '',
        });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.email'));
    };

    return (
        <>
            <Head title="Esqueci minha senha" />

            <main className="grid min-h-screen bg-gray-50 lg:grid-cols-2">
                <section className="hidden bg-gradient-to-br from-blue-700 via-blue-600 to-green-500 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <Link
                        href="/"
                        className="inline-flex w-fit items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/20"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Voltar para o início
                    </Link>

                    <div>
                        <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 shadow-lg backdrop-blur">
                            <WalletCards className="h-8 w-8" />
                        </div>

                        <h1 className="max-w-lg text-4xl font-extrabold leading-tight">
                            Recupere o acesso à sua conta com segurança.
                        </h1>

                        <p className="mt-5 max-w-md text-base leading-7 text-blue-50">
                            Informe seu e-mail cadastrado e enviaremos um link
                            seguro para você criar uma nova senha.
                        </p>
                    </div>

                    <p className="text-sm text-blue-100">
                        Controle Financeiro Lucas Silva ©{' '}
                        {new Date().getFullYear()}
                    </p>
                </section>

                <section className="flex items-center justify-center px-6 py-10">
                    <div className="w-full max-w-md">
                        <div className="mb-8 text-center lg:text-left">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-green-500 text-white shadow-md lg:mx-0">
                                <Mail className="h-7 w-7" />
                            </div>

                            <h2 className="text-3xl font-bold text-gray-950">
                                Esqueci minha senha
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-gray-600">
                                Informe o e-mail da sua conta para receber o
                                link de redefinição de senha.
                            </p>
                        </div>

                        <div className="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            {status && (
                                <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium leading-6 text-green-700">
                                    {status}
                                </div>
                            )}

                            <form
                                className="flex flex-col gap-6"
                                onSubmit={submit}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="email">E-mail</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(event) =>
                                            setData(
                                                'email',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="seuemail@exemplo.com"
                                    />

                                    <InputError message={errors.email} />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full bg-blue-600 hover:bg-blue-700"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Mail className="h-4 w-4" />
                                    )}

                                    Enviar link de recuperação
                                </Button>

                                <div className="text-center text-sm text-gray-600">
                                    Lembrou sua senha?{' '}
                                    <TextLink href={route('login')}>
                                        Voltar para entrar
                                    </TextLink>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
