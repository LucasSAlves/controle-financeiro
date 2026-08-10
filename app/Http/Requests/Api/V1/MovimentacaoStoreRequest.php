<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MovimentacaoStoreRequest extends FormRequest
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

            'descricao' => [
                'required',
                'string',
                'max:255',
            ],

            'valor' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'data' => [
                'required',
                'date',
            ],

            'categoria' => [
                'nullable',
                'string',
                'max:255',
            ],

            'forma_pagamento' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'in:pago,pendente,recebido',
            ],

            'observacao' => [
                'nullable',
                'string',
            ],

            'parcelado' => [
                'nullable',
                'boolean',
            ],

            'parcela_fixa' => [
                'nullable',
                'boolean',
            ],

            'total_parcelas' => [
                'nullable',
                'integer',
                'min:2',
                'max:120',
            ],

            'fixo_mensal' => [
                'nullable',
                'boolean',
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

            'descricao.required' =>
                'Informe a descrição.',

            'descricao.string' =>
                'A descrição deve ser um texto.',

            'descricao.max' =>
                'A descrição não pode ter mais que 255 caracteres.',

            'valor.required' =>
                'Informe o valor.',

            'valor.numeric' =>
                'Informe um valor válido.',

            'valor.min' =>
                'O valor deve ser maior que zero.',

            'data.required' =>
                'Informe a data.',

            'data.date' =>
                'Informe uma data válida.',

            'categoria.string' =>
                'A categoria deve ser um texto.',

            'categoria.max' =>
                'A categoria não pode ter mais que 255 caracteres.',

            'forma_pagamento.string' =>
                'A forma de pagamento deve ser um texto.',

            'forma_pagamento.max' =>
                'A forma de pagamento não pode ter mais que 255 caracteres.',

            'status.required' =>
                'Informe o status.',

            'status.in' =>
                'Informe um status válido.',

            'observacao.string' =>
                'A observação deve ser um texto.',

            'parcelado.boolean' =>
                'O campo parcelado deve ser verdadeiro ou falso.',

            'parcela_fixa.boolean' =>
                'O campo parcela fixa deve ser verdadeiro ou falso.',

            'fixo_mensal.boolean' =>
                'O campo entrada fixa mensal deve ser verdadeiro ou falso.',

            'total_parcelas.integer' =>
                'A quantidade de parcelas deve ser um número inteiro.',

            'total_parcelas.min' =>
                'A quantidade de parcelas deve ser no mínimo 2.',

            'total_parcelas.max' =>
                'A quantidade de parcelas não pode ser maior que 120.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tipo = $this->input('tipo');

                $parcelado = $this->boolean(
                    'parcelado'
                );

                $parcelaFixa = $this->boolean(
                    'parcela_fixa'
                );

                $fixoMensal = $this->boolean(
                    'fixo_mensal'
                );

                if (
                    $parcelado
                    && $tipo !== 'despesa'
                ) {
                    $validator->errors()->add(
                        'parcelado',
                        'O parcelamento está disponível apenas para despesas.'
                    );
                }

                if (
                    $parcelaFixa
                    && $tipo !== 'despesa'
                ) {
                    $validator->errors()->add(
                        'parcela_fixa',
                        'A despesa fixa mensal está disponível apenas para despesas.'
                    );
                }

                if (
                    $parcelado
                    && $parcelaFixa
                ) {
                    $validator->errors()->add(
                        'parcela_fixa',
                        'Escolha entre compra parcelada ou despesa fixa mensal.'
                    );
                }

                if (
                    $fixoMensal
                    && $tipo !== 'entrada'
                ) {
                    $validator->errors()->add(
                        'fixo_mensal',
                        'Entrada fixa mensal está disponível apenas para entradas.'
                    );
                }

                if (
                    $parcelado
                    && ! $this->filled('total_parcelas')
                ) {
                    $validator->errors()->add(
                        'total_parcelas',
                        'Informe a quantidade de parcelas.'
                    );
                }
            },
        ];
    }
}
