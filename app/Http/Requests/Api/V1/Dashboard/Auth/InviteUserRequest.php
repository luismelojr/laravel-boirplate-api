<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(fn ($query) => $query->where('tenant_id', auth()->user()->tenant_id)),
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'user'])],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado neste tenant.',
            'role.required' => 'O papel é obrigatório.',
            'role.in' => 'O papel deve ser admin ou user.',
        ];
    }
}
