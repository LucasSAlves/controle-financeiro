<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(
            function (User $user, string $token): MailMessage {
                $url = url(route('password.reset', [
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ], false));

                $tempoExpiracao = config(
                    'auth.passwords.'.config('auth.defaults.passwords').'.expire',
                    60
                );

                return (new MailMessage)
                    ->subject('Redefinição de senha - Controle Financeiro')
                    ->view('emails.password-reset', [
                        'user' => $user,
                        'url' => $url,
                        'tempoExpiracao' => $tempoExpiracao,
                    ]);
            }
        );
    }
}
