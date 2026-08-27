<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => [
                'required',
                'in:entrada,despesa',
            ],

            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'categorias',
                    'nome'
                )->where(function ($query) {
                    return $query
                        ->where(
                            'user_id',
                            $this->user()->id
                        )
                        ->where(
                            'tipo',
                            $this->input('tipo')
                        );
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' =>
                'Informe o tipo da categoria.',

            'tipo.in' =>
                'O tipo deve ser entrada ou despesa.',

            'nome.required' =>
                'Informe o nome da categoria.',

            'nome.string' =>
                'O nome da categoria deve ser um texto.',

            'nome.max' =>
                'O nome da categoria não pode ter mais que 255 caracteres.',

            'nome.unique' =>
                'Já existe uma categoria com este nome para este tipo.',
        ];
    }
}
