import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Eye,
    EyeOff,
    KeyRound,
    LoaderCircle,
    ShieldCheck,
    WalletCards,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

interface ResetPasswordForm {
    [key: string]: string;
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function ResetPassword({
    token,
    email,
}: ResetPasswordProps) {
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] =
        useState(false);

    const { data, setData, post, processing, errors, reset } =
        useForm<ResetPasswordForm>({
            token,
            email,
            password: '',
            password_confirmation: '',
        });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.store'), {
            onFinish: () =>
                reset('password', 'password_confirmation'),
        });
    };

return (
        <>
            <Head title="Redefinir senha" />

            <main className="grid min-h-screen bg-gray-50 lg:grid-cols-2">
                <section className="hidden bg-gradient-to-br from-blue-700 via-blue-600 to-green-500 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <Link
                        href={route('login')}
                        className="inline-flex w-fit items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/20"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Voltar para entrar
                    </Link>

                    <div>
                        <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 shadow-lg backdrop-blur">
                            <WalletCards className="h-8 w-8" />
                        </div>

                        <h1 className="max-w-lg text-4xl font-extrabold leading-tight">
                            Crie uma nova senha para sua conta.
                        </h1>

                        <p className="mt-5 max-w-md text-base leading-7 text-blue-50">
                            Escolha uma senha segura e diferente da senha
                            utilizada atualmente.
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
                                <KeyRound className="h-7 w-7" />
                            </div>

                            <h2 className="text-3xl font-bold text-gray-950">
                                Redefinir senha
                            </h2>

                            <p className="mt-2 text-sm leading-6 text-gray-600">
                                Informe e confirme sua nova senha para recuperar
                                o acesso à conta.
                            </p>
                        </div>

                        <div className="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <form
                                className="flex flex-col gap-5"
                                onSubmit={submit}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="email">E-mail</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        autoComplete="email"
                                        value={data.email}
                                        readOnly
                                        className="bg-gray-50 text-gray-600"
                                    />

                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Nova senha
                                    </Label>

                                    <div className="relative">
                                        <Input
                                            id="password"
                                            type={
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            name="password"
                                            required
                                            autoFocus
                                            autoComplete="new-password"
                                            value={data.password}
                                            onChange={(event) =>
                                                setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Digite sua nova senha"
                                            className="pr-11"
                                        />

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowPassword(
                                                    (current) => !current,
                                                )
                                            }
                                            className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 transition hover:text-gray-800"
                                            aria-label={
                                                showPassword
                                                    ? 'Ocultar senha'
                                                    : 'Mostrar senha'
                                            }
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>

                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar nova senha
                                    </Label>

                                    <div className="relative">
                                        <Input
                                            id="password_confirmation"
                                            type={
                                                showPasswordConfirmation
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            value={
                                                data.password_confirmation
                                            }
                                            onChange={(event) =>
                                                setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Digite novamente a senha"
                                            className="pr-11"
                                        />

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowPasswordConfirmation(
                                                    (current) => !current,
                                                )
                                            }
                                            className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 transition hover:text-gray-800"
                                            aria-label={
                                                showPasswordConfirmation
                                                    ? 'Ocultar confirmação da senha'
                                                    : 'Mostrar confirmação da senha'
                                            }
                                        >
                                            {showPasswordConfirmation ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>

                                    <InputError
                                        message={
                                            errors.password_confirmation
                                        }
                                    />
                                </div>

                                <div className="flex gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-700">
                                    <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0" />

                                    <p>
                                        Use uma senha segura e diferente da sua
                                        senha atual.
                                    </p>
                                </div>

                                <Button
                                    type="submit"
                                    className="mt-1 w-full bg-blue-600 hover:bg-blue-700"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <KeyRound className="h-4 w-4" />
                                    )}

                                    Salvar nova senha
                                </Button>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
