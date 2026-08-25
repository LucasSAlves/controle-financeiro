<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => [
                'required',
                Password::defaults(),
                'confirmed',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' =>
                'Informe sua senha atual.',

            'current_password.current_password' =>
                'A senha atual está incorreta.',

            'password.required' =>
                'Informe a nova senha.',

            'password.confirmed' =>
                'A confirmação da senha não confere.',

            'password.min' =>
                'A nova senha deve ter pelo menos :min caracteres.',

            'password.letters' =>
                'A nova senha deve conter pelo menos uma letra.',

            'password.mixed' =>
                'A nova senha deve conter letras maiúsculas e minúsculas.',

            'password.numbers' =>
                'A nova senha deve conter pelo menos um número.',

            'password.symbols' =>
                'A nova senha deve conter pelo menos um símbolo.',

            'password.uncompromised' =>
                'Esta senha apareceu em vazamentos de dados. Escolha outra senha.',
        ];
    }
}
