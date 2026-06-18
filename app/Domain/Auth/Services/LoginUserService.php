<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\LoginUserData;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    public function handle(LoginUserData $data): array
    {
        $user = User::query()
            ->where('email', $data->email)
            ->first();

        if (! $user || $user->status === UserStatusEnum::Inactive) {
            throw new AuthenticationException('Credenciais inválidas');
        }

        if (! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('Credenciais inválidas');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('E-mail não verificado. Verifique sua caixa de entrada.');
        }

        $user->tokens()->where('name', 'auth_token')->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
