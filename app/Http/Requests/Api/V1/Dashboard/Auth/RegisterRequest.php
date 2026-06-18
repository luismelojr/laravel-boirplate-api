<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Dashboard\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_name.required' => 'O nome do tenant é obrigatório.',
            'tenant_name.max' => 'O nome do tenant não pode ter mais de :max caracteres.',
            'tenant_slug.required' => 'O slug do tenant é obrigatório.',
            'tenant_slug.max' => 'O slug não pode ter mais de :max caracteres.',
            'tenant_slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'tenant_slug.unique' => 'Este slug já está em uso.',
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de :max caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de :max caracteres.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação de senha não coincide.',
        ];
    }
}
