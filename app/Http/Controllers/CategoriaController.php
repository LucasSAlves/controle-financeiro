<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index(): Response
    {
        $categorias = Categoria::where('user_id', Auth::id())
            ->where('ativo', true)
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'tipo' => $categoria->tipo,
                    'nome' => $categoria->nome,
                    'ativo' => $categoria->ativo,
                ];
            });

        return Inertia::render('categorias/index', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:entrada,despesa'],
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias')->where(function ($query) use ($request) {
                    return $query
                        ->where('user_id', Auth::id())
                        ->where('tipo', $request->tipo);
                }),
            ],
        ]);

        Categoria::create([
            'user_id' => Auth::id(),
            'tipo' => $dados['tipo'],
            'nome' => $dados['nome'],
            'ativo' => true,
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoria cadastrada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $categoria = Categoria::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $dados = $request->validate([
            'tipo' => ['required', 'in:entrada,despesa'],
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nome')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('user_id', Auth::id())
                            ->where('tipo', $request->tipo);
                    })
                    ->ignore($categoria->id),
            ],
        ]);

        $categoria->update([
            'tipo' => $dados['tipo'],
            'nome' => $dados['nome'],
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $categoria = Categoria::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $categoria->update([
            'ativo' => false,
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoria excluída com sucesso.');
    }
}
