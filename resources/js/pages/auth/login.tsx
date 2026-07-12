import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, LockKeyhole, WalletCards } from 'lucide-react';
import { FormEventHandler } from 'react';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Entrar" />

            <main className="grid min-h-screen bg-gray-50 lg:grid-cols-2">
                <section className="hidden bg-gradient-to-br from-blue-700 via-blue-600 to-green-500 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <Link
                        href="/"
                        className="inline-flex w-fit items-center gap-2 rounded-xl bg-white/10 px-4 py-2 text-sm font-medium text-white backdrop-blur transition hover:bg-white/20"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Voltar para início
                    </Link>

                    <div>
                        <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-3xl bg-white/15 shadow-lg backdrop-blur">
                            <WalletCards className="h-8 w-8" />
                        </div>

                        <h1 className="max-w-lg text-4xl font-extrabold leading-tight">
                            Controle suas finanças com mais clareza.
                        </h1>

                        <p className="mt-5 max-w-md text-base leading-7 text-blue-50">
                            Acompanhe receitas, despesas, parcelas, contas vencidas
                            e receba avisos automáticos de vencimento.
                        </p>
                    </div>

                    <p className="text-sm text-blue-100">
                        Controle Financeiro Lucas Silva © {new Date().getFullYear()}
                    </p>
                </section>

                <section className="flex items-center justify-center px-6 py-10">
                    <div className="w-full max-w-md">
                        <div className="mb-8 text-center lg:text-left">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-green-500 text-xl font-bold text-white shadow-md lg:mx-0">
                                CF
                            </div>

                            <h2 className="text-3xl font-bold text-gray-950">
                                Entrar no sistema
                            </h2>

                            <p className="mt-2 text-sm text-gray-600">
                                Informe seu e-mail e senha para acessar sua conta.
                            </p>
                        </div>

                        <div className="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            {status && (
                                <div className="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                                    {status}
                                </div>
                            )}

                            <form className="flex flex-col gap-6" onSubmit={submit}>
                                <div className="grid gap-5">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">E-mail</Label>

                                        <Input
                                            id="email"
                                            type="email"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="email"
                                            value={data.email}
                                            onChange={(e) =>
                                                setData('email', e.target.value)
                                            }
                                            placeholder="seuemail@exemplo.com"
                                        />

                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <div className="flex items-center">
                                            <Label htmlFor="password">Senha</Label>

                                            {canResetPassword && (
                                                <TextLink
                                                    href={route('password.request')}
                                                    className="ml-auto text-sm"
                                                    tabIndex={5}
                                                >
                                                    Esqueci minha senha
                                                </TextLink>
                                            )}
                                        </div>

                                        <Input
                                            id="password"
                                            type="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(e) =>
                                                setData('password', e.target.value)
                                            }
                                            placeholder="Digite sua senha"
                                        />

                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <Checkbox
                                            id="remember"
                                            name="remember"
                                            tabIndex={3}
                                            checked={data.remember}
                                            onCheckedChange={(checked) =>
                                                setData('remember', Boolean(checked))
                                            }
                                        />

                                        <Label htmlFor="remember">
                                            Manter conectado
                                        </Label>
                                    </div>

                                    <Button
                                        type="submit"
                                        className="mt-2 w-full bg-blue-600 hover:bg-blue-700"
                                        tabIndex={4}
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <LockKeyhole className="h-4 w-4" />
                                        )}

                                        Entrar
                                    </Button>
                                </div>

                                <div className="text-center text-sm text-gray-600">
                                    Ainda não tem uma conta?{' '}
                                    <TextLink href={route('register')} tabIndex={5}>
                                        Criar conta
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
