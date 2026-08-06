<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MovimentacaoIndexRequest extends FormRequest
{
    /**
     * A autenticação e a situação do usuário são verificadas
     * pelos middlewares auth:sanctum e api.active.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Valida os filtros da listagem de movimentações.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mes' => [
                'nullable',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
            ],

            'tipo' => [
                'nullable',
                'string',
                'in:todos,entrada,despesa',
            ],

            'status' => [
                'nullable',
                'string',
                'in:todos,pago,pendente,recebido',
            ],
        ];
    }

    /**
     * Mensagens de validação retornadas pelo aplicativo.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mes.regex' =>
                'Informe o mês no formato AAAA-MM.',

            'tipo.in' =>
                'Informe um tipo válido: todos, entrada ou despesa.',

            'status.in' =>
                'Informe um status válido: todos, pago, pendente ou recebido.',
        ];
    }
}
