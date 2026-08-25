<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDadosUsuarioRequest extends FormRequest
{
    /**
     * O usuário autenticado pode atualizar seus próprios dados.
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
            'receber_aviso_email' => $this->boolean(
                'receber_aviso_email'
            ),
        ]);
    }

    /**
     * Regras de validação.
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
                Rule::unique(User::class)
                    ->ignore($this->user()->id),
            ],

            'receber_aviso_email' => [
                'required',
                'boolean',
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

            'receber_aviso_email.required' =>
                'Informe se deseja receber avisos por e-mail.',
        ];
    }
}
