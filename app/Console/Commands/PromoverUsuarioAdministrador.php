<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoverUsuarioAdministrador extends Command
{
    /**
     * Nome, argumentos e opções do comando.
     *
     * @var string
     */
    protected $signature = 'usuario:promover-administrador
                            {email : E-mail do usuário}
                            {--principal : Define o usuário como administrador principal protegido}';

    /**
     * Descrição do comando.
     *
     * @var string
     */
    protected $description = 'Promove um usuário existente para administrador';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = mb_strtolower(
            trim((string) $this->argument('email'))
        );

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            $this->error("Nenhum usuário foi encontrado com o e-mail {$email}.");

            return self::FAILURE;
        }

        $definirComoPrincipal = (bool) $this->option('principal');

        if ($definirComoPrincipal) {
            $outroAdministradorPrincipal = User::query()
                ->where('is_primary_admin', true)
                ->where('id', '!=', $user->id)
                ->first();

            if ($outroAdministradorPrincipal) {
                $this->error(
                    "Já existe um administrador principal: {$outroAdministradorPrincipal->email}."
                );

                return self::FAILURE;
            }
        }

        $user->forceFill([
            'is_admin' => true,
            'is_active' => true,
            'is_primary_admin' => (
                $definirComoPrincipal || $user->is_primary_admin
            ),
        ])->save();

        $this->newLine();
        $this->info('Usuário promovido com sucesso.');
        $this->line("Nome: {$user->name}");
        $this->line("E-mail: {$user->email}");
        $this->line('Administrador: sim');
        $this->line('Acesso ativo: sim');
        $this->line(
            'Administrador principal: '.
            ($user->is_primary_admin ? 'sim' : 'não')
        );

        return self::SUCCESS;
    }
}
