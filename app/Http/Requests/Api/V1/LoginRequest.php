<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Qualquer visitante pode tentar realizar o login.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação do login do aplicativo.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
            'device_name' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Mensagens de validação em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail não pode possuir mais de 255 caracteres.',
            'password.required' => 'Informe a senha.',
            'password.max' => 'A senha não pode possuir mais de 255 caracteres.',
            'device_name.required' => 'Informe a identificação do dispositivo.',
            'device_name.max' => 'A identificação do dispositivo não pode possuir mais de 100 caracteres.',
        ];
    }
}
