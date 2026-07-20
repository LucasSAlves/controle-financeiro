<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Exibe a lista de usuários cadastrados.
     */
    public function index(): Response
    {
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'is_admin',
                'is_active',
                'is_primary_admin',
                'created_at',
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }

    /**
     * Bloqueia o acesso de um usuário.
     */
    public function block(Request $request, User $user): RedirectResponse
    {
        if ($user->is_primary_admin) {
            return back()->with(
                'error',
                'O administrador principal não pode ser bloqueado.',
            );
        }

        if ($request->user()?->is($user)) {
            return back()->with(
                'error',
                'Você não pode bloquear sua própria conta.',
            );
        }

        if (! $user->is_active) {
            return back()->with(
                'info',
                "{$user->name} já está com o acesso bloqueado."
            );
        }

        $user->forceFill([
            'is_active' => false,
        ])->save();

        return back()->with(
            'success',
            "O acesso de {$user->name} foi bloqueado com sucesso."
        );
    }

    /**
     * Reativa o acesso de um usuário.
     */
    public function activate(User $user): RedirectResponse
    {
        if ($user->is_active) {
            return back()->with(
                'info',
                "{$user->name} já está com o acesso ativo."
            );
        }

        $user->forceFill([
            'is_active' => true,
        ])->save();

        return back()->with(
            'success',
            "O acesso de {$user->name} foi reativado com sucesso."
        );
    }

    /**
 * Promove um usuário comum para administrador.
 */
public function promote(User $user): RedirectResponse
{
    if ($user->is_admin) {
        return back()->with(
            'info',
            "{$user->name} já é administrador."
        );
    }

    $user->forceFill([
        'is_admin' => true,
    ])->save();

    return back()->with(
        'success',
        "{$user->name} foi promovido a administrador com sucesso."
    );
}

/**
 * Remove a permissão administrativa de um usuário.
 */
public function demote(Request $request, User $user): RedirectResponse
    {
        if ($user->is_primary_admin) {
            return back()->with(
                'error',
                'A permissão do administrador principal não pode ser removida.'
            );
        }

        if ($request->user()?->is($user)) {
            return back()->with(
                'error',
                'Você não pode remover sua própria permissão de administrador.'
            );
        }

        if (! $user->is_admin) {
            return back()->with(
                'info',
                "{$user->name} já é um usuário comum."
            );
        }

        $user->forceFill([
            'is_admin' => false,
        ])->save();

        return back()->with(
            'success',
            "{$user->name} agora é um usuário comum."
        );
    }
}
