<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Qualquer visitante pode criar uma conta.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os dados antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => mb_strtolower(
                trim((string) $this->email),
            ),
        ]);
    }

    /**
     * Regras de validação do cadastro do aplicativo.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
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
            'name.required' => 'Informe o nome.',
            'name.max' => 'O nome não pode possuir mais de 255 caracteres.',

            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail não pode possuir mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',

            'password.required' => 'Informe a senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',

            'device_name.required' => 'Informe a identificação do dispositivo.',
            'device_name.max' => 'A identificação do dispositivo não pode possuir mais de 100 caracteres.',
        ];
    }
}
