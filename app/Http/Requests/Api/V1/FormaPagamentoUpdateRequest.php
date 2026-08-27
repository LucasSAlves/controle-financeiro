<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormaPagamentoUpdateRequest extends FormRequest
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
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'formas_pagamento',
                    'nome'
                )
                    ->where(function ($query) {
                        return $query->where(
                            'user_id',
                            $this->user()->id
                        );
                    })
                    ->ignore(
                        $this->route('id')
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' =>
                'Informe o nome da forma de pagamento.',

            'nome.string' =>
                'O nome da forma de pagamento deve ser um texto.',

            'nome.max' =>
                'O nome da forma de pagamento não pode ter mais que 255 caracteres.',

            'nome.unique' =>
                'Já existe uma forma de pagamento com este nome.',
        ];
    }
}
