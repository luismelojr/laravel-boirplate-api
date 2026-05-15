<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Data\LoginUserData;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginUserService
{
    public function handle(LoginUserData $data): array
    {
        try {
            $user = User::query()
                ->where('email', $data->email)
                ->first();

            if (! $user || ! Hash::check($data->password, $user->password)) {
                throw new AuthenticationException('Credenciais inválidas');
            }

            if ($user->status === UserStatusEnum::Inactive) {
                throw new AuthenticationException('Usuário inativo. Contate o administrador do sistema.');
            }

            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->where('name', 'auth_token')
                ->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Throwable $exception) {
            report($exception);
            throw $exception;
        }
    }
}
