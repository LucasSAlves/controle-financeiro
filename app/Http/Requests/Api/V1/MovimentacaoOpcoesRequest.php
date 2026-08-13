<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MovimentacaoOpcoesRequest extends FormRequest
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
                'string',
                'in:entrada,despesa',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' =>
                'Informe o tipo da movimentação.',

            'tipo.in' =>
                'O tipo deve ser entrada ou despesa.',
        ];
    }
}
