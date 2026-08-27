<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CategoriaStoreRequest;
use App\Http\Requests\Api\V1\CategoriaUpdateRequest;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $categorias = Categoria::query()
            ->where('user_id', $user->id)
            ->where('ativo', true)
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get()
            ->map(function (Categoria $categoria): array {
                return [
                    'id' => $categoria->id,
                    'tipo' => $categoria->tipo,
                    'nome' => $categoria->nome,
                    'ativo' => $categoria->ativo,
                ];
            })
            ->values();

        return response()->json([
            'categorias' => $categorias,
        ]);
    }

    public function store(
        CategoriaStoreRequest $request
    ): JsonResponse {
        $user = $request->user();
        $dados = $request->validated();

        $categoria = Categoria::create([
            'user_id' => $user->id,
            'tipo' => $dados['tipo'],
            'nome' => $dados['nome'],
            'ativo' => true,
        ]);

        return response()->json([
            'message' =>
                'Categoria cadastrada com sucesso.',

            'categoria' => [
                'id' => $categoria->id,
                'tipo' => $categoria->tipo,
                'nome' => $categoria->nome,
                'ativo' => $categoria->ativo,
            ],
        ], 201);
    }

    public function update(
        CategoriaUpdateRequest $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        $categoria = Categoria::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $dados = $request->validated();

        $categoria->update([
            'tipo' => $dados['tipo'],
            'nome' => $dados['nome'],
        ]);

        return response()->json([
            'message' =>
                'Categoria atualizada com sucesso.',

            'categoria' => [
                'id' => $categoria->id,
                'tipo' => $categoria->tipo,
                'nome' => $categoria->nome,
                'ativo' => $categoria->ativo,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $user = $request->user();

        $categoria = Categoria::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $categoria->update([
            'ativo' => false,
        ]);

        return response()->json([
            'message' =>
                'Categoria excluída com sucesso.',
        ]);
    }
}
